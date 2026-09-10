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
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = Debt::query()->where('room_id', $room->id)->with(['roomUser.globalUser', 'campaign'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('campaign_id')) $query->where('campaign_id', $request->integer('campaign_id'));
        return response()->json(['data' => $query->paginate(50)]);
    }

    /**
     * Handle the show operation.
     * @param Debt $debt Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Debt $debt): JsonResponse
    {
        $this->assertRoom($debt);
        return response()->json(['data' => $debt->load(['roomUser.globalUser', 'campaign', 'adjustments', 'payments'])]);
    }

    /**
     * Handle the pay operation.
     * @param Debt $debt Parameter value.
     * @param DebtPaymentRequest $request Parameter value.
     * @param RecordDebtPaymentAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function pay(Debt $debt, DebtPaymentRequest $request, RecordDebtPaymentAction $action): JsonResponse
    {
        $this->assertRoom($debt);
        return response()->json(['data' => $action->execute($debt, (int) $request->validated('amount'), $request->validated('payment_method'), $request->validated('reference'))]);
    }

    /**
     * Handle the adjust operation.
     * @param Debt $debt Parameter value.
     * @param DebtAdjustmentRequest $request Parameter value.
     * @param AdjustDebtAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function adjust(Debt $debt, DebtAdjustmentRequest $request, AdjustDebtAction $action): JsonResponse
    {
        $this->assertRoom($debt);
        $data = $request->validated();
        return response()->json(['data' => $action->execute($debt, $data['type'], (int) $data['amount'], $data['reason'])]);
    }

    /**
     * Handle the status operation.
     * @param Debt $debt Parameter value.
     * @param SetDebtStatusRequest $request Parameter value.
     * @param SetDebtStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function status(Debt $debt, SetDebtStatusRequest $request, SetDebtStatusAction $action): JsonResponse
    {
        $this->assertRoom($debt);
        return response()->json(['data' => $action->execute($debt, $request->validated('status'))]);
    }

    /**
     * Handle the assert room operation.
     * @param Debt $debt Parameter value.
     * @return void Result of the operation.
     */
    private function assertRoom(Debt $debt): void
    {
        abort_unless($debt->room_id === request()->attributes->get('room')->id, 404);
    }
}
