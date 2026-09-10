<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\SavePaymentAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentAccountRequest;
use App\Models\PaymentAccount;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;

class PaymentAccountController extends Controller
{
    /**
     * Handle the index operation.
     * @return JsonResponse Result of the operation.
     */
    public function index(): JsonResponse
    {
        $room = request()->attributes->get('room');
        return response()->json(['data' => $room->paymentAccounts()->paginate(20)->through(fn (PaymentAccount $account) => $this->payload($account))]);
    }

    /**
     * Handle the store operation.
     * @param StorePaymentAccountRequest $request Parameter value.
     * @param SavePaymentAccountAction $action Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(StorePaymentAccountRequest $request, SavePaymentAccountAction $action, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        $account = $action->execute($room, $request->validated());
        $audit->record('payment_account.created', 'payment_account', $account->id, $room->id, [], ['bank_code' => $account->bank_code, 'status' => $account->status]);
        return response()->json(['data' => $this->payload($account)], 201);
    }

    /**
     * Handle the update operation.
     * @param StorePaymentAccountRequest $request Parameter value.
     * @param PaymentAccount $account Parameter value.
     * @param SavePaymentAccountAction $action Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(StorePaymentAccountRequest $request, PaymentAccount $account, SavePaymentAccountAction $action, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($account->room_id === $room->id, 404);
        $before = ['bank_code' => $account->bank_code, 'status' => $account->status, 'is_default' => $account->is_default];
        $account = $action->execute($room, $request->validated(), $account);
        $audit->record('payment_account.updated', 'payment_account', $account->id, $room->id, $before, ['bank_code' => $account->bank_code, 'status' => $account->status, 'is_default' => $account->is_default]);
        return response()->json(['data' => $this->payload($account)]);
    }

    /**
     * Handle the destroy operation.
     * @param PaymentAccount $account Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(PaymentAccount $account, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($account->room_id === $room->id, 404);
        $account->update(['status' => 'disabled', 'is_default' => false]);
        $audit->record('payment_account.disabled', 'payment_account', $account->id, $room->id, ['status' => 'active'], ['status' => 'disabled']);
        return response()->json(['data' => ['disabled' => true]]);
    }

    /**
     * Handle the payload operation.
     * @param PaymentAccount $account Parameter value.
     * @return array Result of the operation.
     */
    private function payload(PaymentAccount $account): array
    {
        return ['id' => $account->id, 'bank_code' => $account->bank_code, 'bank_name' => $account->bank_name, 'account_number' => $this->mask($account->account_number), 'account_name' => $account->account_name, 'is_default' => (bool) $account->is_default, 'status' => $account->status];
    }

    /**
     * Handle the mask operation.
     * @param string $number Parameter value.
     * @return string Result of the operation.
     */
    private function mask(string $number): string
    {
        return strlen($number) <= 4 ? str_repeat('â€¢', strlen($number)) : str_repeat('â€¢', max(0, strlen($number) - 4)).substr($number, -4);
    }
}
