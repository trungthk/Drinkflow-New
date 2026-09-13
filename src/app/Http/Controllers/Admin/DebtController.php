<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Debt\AdjustDebtAction;
use App\Actions\Debt\RecordDebtPaymentAction;
use App\Actions\Debt\SetDebtStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DebtAdjustmentRequest;
use App\Http\Requests\DebtPaymentRequest;
use App\Http\Requests\SetDebtStatusRequest;
use App\Models\Debt;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    /**
     * Handle the index operation.
     */
    public function index(Request $request, Room $room): JsonResponse
    {
        $query = Debt::query()->where('room_id', $room->id)->with(['roomUser.globalUser', 'campaign'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('campaign_id')) $query->where('campaign_id', $request->integer('campaign_id'));
        return response()->json(['data' => $query->paginate(50)]);
    }

    /**
     * Display the standalone Dual-Debt & Accounting Ledger view.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @param \App\Services\Admin\AdminDebtService $debtService Debt summary service.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room, \App\Services\Admin\AdminDebtService $debtService): View
    {
        $debts = Debt::query()
            ->where('room_id', $room->id)
            ->with(['roomUser.globalUser', 'campaign'])
            ->latest()
            ->paginate(50);

        $summary = $debtService->getLedgerSummary($room);

        return view('admin.debts', array_merge([
            'room' => $room,
            'debts' => $debts,
        ], $summary));
    }

    /**
     * Handle the show operation.
     */
    public function show(Room $room, Debt $debt): JsonResponse
    {
        $this->assertRoom($room, $debt);
        return response()->json(['data' => $debt->load(['roomUser.globalUser', 'campaign', 'adjustments', 'payments'])]);
    }

    /**
     * Handle the pay operation.
     */
    public function pay(Room $room, Debt $debt, DebtPaymentRequest $request, RecordDebtPaymentAction $action): JsonResponse
    {
        $this->assertRoom($room, $debt);
        return response()->json(['data' => $action->execute($debt, (int) $request->validated('amount'), $request->validated('payment_method'), $request->validated('reference'))]);
    }

    /**
     * Handle the adjust operation.
     */
    public function adjust(Room $room, Debt $debt, DebtAdjustmentRequest $request, AdjustDebtAction $action): JsonResponse
    {
        $this->assertRoom($room, $debt);
        $data = $request->validated();
        return response()->json(['data' => $action->execute($debt, $data['type'], (int) $data['amount'], $data['reason'])]);
    }

    /**
     * Handle the status operation.
     */
    public function status(Room $room, Debt $debt, SetDebtStatusRequest $request, SetDebtStatusAction $action): JsonResponse
    {
        $this->assertRoom($room, $debt);
        return response()->json(['data' => $action->execute($debt, $request->validated('status'))]);
    }

    /**
     * Assert that the debt belongs to the current room.
     */
    private function assertRoom(Room $room, Debt $debt): void
    {
        abort_unless($debt->room_id === $room->id, 404);
    }
}
