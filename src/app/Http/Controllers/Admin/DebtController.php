<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Debt\AdjustDebtAction;
use App\Actions\Debt\RecordDebtPaymentAction;
use App\Actions\Debt\SetDebtStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DebtAdjustmentRequest;
use App\Http\Requests\DebtPaymentRequest;
use App\Http\Requests\SetDebtStatusRequest;
use App\Models\Debt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = Debt::query()->where('room_id', $room->id)->with(['roomUser.globalUser', 'campaign'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('campaign_id')) $query->where('campaign_id', $request->integer('campaign_id'));
        return response()->json(['data' => $query->paginate(50)]);
    }

    public function show(Debt $debt): JsonResponse
    {
        $this->assertRoom($debt);
        return response()->json(['data' => $debt->load(['roomUser.globalUser', 'campaign', 'adjustments', 'payments'])]);
    }

    public function pay(Debt $debt, DebtPaymentRequest $request, RecordDebtPaymentAction $action): JsonResponse
    {
        $this->assertRoom($debt);
        return response()->json(['data' => $action->execute($debt, (int) $request->validated('amount'), $request->validated('payment_method'), $request->validated('reference'))]);
    }

    public function adjust(Debt $debt, DebtAdjustmentRequest $request, AdjustDebtAction $action): JsonResponse
    {
        $this->assertRoom($debt);
        $data = $request->validated();
        return response()->json(['data' => $action->execute($debt, $data['type'], (int) $data['amount'], $data['reason'])]);
    }

    public function status(Debt $debt, SetDebtStatusRequest $request, SetDebtStatusAction $action): JsonResponse
    {
        $this->assertRoom($debt);
        return response()->json(['data' => $action->execute($debt, $request->validated('status'))]);
    }

    private function assertRoom(Debt $debt): void
    {
        abort_unless($debt->room_id === request()->attributes->get('room')->id, 404);
    }
}
