<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Debt\AdjustDebtAction;
use App\Actions\Debt\RecordDebtPaymentAction;
use App\Actions\Debt\SetDebtStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DebtAdjustmentRequest;
use App\Http\Requests\DebtPaymentRequest;
use App\Http\Requests\RemindDebtsRequest;
use App\Http\Requests\SetDebtStatusRequest;
use App\Http\Requests\SettleDebtsRequest;
use App\Models\Debt;
use App\Models\Room;
use App\Services\Debt\DebtReminderService;
use App\Services\Debt\DebtSettlementService;
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
        $ledger = Debt::query()->where('room_id', $room->id);
        return response()->json(['data' => $query->paginate(50), 'summary' => [
            'by_user' => (clone $ledger)->selectRaw('room_user_id, SUM(remaining_amount) as remaining_amount, COUNT(*) as debt_count')->groupBy('room_user_id')->with('roomUser.globalUser:id,name,email')->get(),
            'by_day' => (clone $ledger)->selectRaw('DATE(created_at) as date, SUM(original_amount) as original_amount, SUM(remaining_amount) as remaining_amount, COUNT(*) as debt_count')->groupByRaw('DATE(created_at)')->orderBy('date')->get(),
        ]]);
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
     * Approve a pending payment and clear remaining debt balance.
     *
     * @param Room $room Current room model.
     * @param Debt $debt Debt entity.
     * @param \App\Actions\Debt\ApproveDebtPaymentAction $action Domain action to approve debt payment.
     * @return JsonResponse Approved debt payload.
     */
    public function approve(Room $room, Debt $debt, \App\Actions\Debt\ApproveDebtPaymentAction $action): JsonResponse
    {
        $this->assertRoom($room, $debt);
        return response()->json([
            'data' => $action->execute($debt),
            'message' => __('admin.payment_approved_successfully'),
        ]);
    }

    /**
     * Settle every outstanding debt selected by a campaign, date, member, or explicit debt IDs.
     *
     * @param Request $request Incoming request.
     * @param Room $room Current room.
     * @param DebtSettlementService $service Settlement service.
     * @return JsonResponse Settled debts.
     */
    public function settle(SettleDebtsRequest $request, Room $room, DebtSettlementService $service): JsonResponse
    {
        return response()->json(['data' => $service->settle($room, $request->validated())]);
    }

    /**
     * Send browser and configured-channel reminders for selected outstanding debts.
     *
     * @param Request $request Incoming request.
     * @param Room $room Current room.
     * @param DebtSettlementService $settlements Debt selection service.
     * @param DebtReminderService $reminders Debt reminder service.
     * @return JsonResponse Reminder result.
     */
    public function remind(RemindDebtsRequest $request, Room $room, DebtSettlementService $settlements, DebtReminderService $reminders): JsonResponse
    {
        $data = $request->validated();
        $debts = $settlements->selectedOutstandingDebts($room, $data);
        abort_if($debts->isEmpty(), 422, __('admin.no_outstanding_debts_selected'));

        return response()->json(['data' => ['notified' => $reminders->remind($room, $debts)]]);
    }

    /**
     * Export the room-scoped debt ledger as a CSV statement.
     *
     * @param Request $request Incoming request.
     * @param Room $room Current room.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse CSV download.
     */
    public function export(Request $request, Room $room): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $debts = Debt::query()->where('room_id', $room->id)->with(['campaign', 'roomUser.globalUser'])->latest()->get();

        return response()->streamDownload(function () use ($debts): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['campaign', 'user_code', 'user', 'date', 'original_amount', 'sponsor_type', 'sponsor_amount', 'paid_amount', 'remaining_amount', 'status']);
            foreach ($debts as $debt) {
                fputcsv($output, [$debt->campaign?->name, $debt->roomUser?->user_code, $debt->roomUser?->globalUser?->email, $debt->created_at?->format('Y-m-d'), $debt->original_amount, $debt->sponsor_type, $debt->sponsor_amount, $debt->paid_amount, $debt->remaining_amount, $debt->status?->value]);
            }
            fclose($output);
        }, 'drinkflow-'.$room->slug.'-debts.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Assert that the debt belongs to the current room.
     */
    private function assertRoom(Room $room, Debt $debt): void
    {
        abort_unless($debt->room_id === $room->id, 404);
    }
}
