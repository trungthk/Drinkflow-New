<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\SavePaymentAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentAccountRequest;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    /**
     * Handle the index operation.
     */
    public function index(Room $room): JsonResponse
    {
        return response()->json(['data' => $room->paymentAccounts()->paginate(20)->through(fn (PaymentAccount $account) => $this->payload($account))]);
    }

    /**
     * Display the standalone VietQR Payment Accounts management view.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room): View
    {
        $accounts = $room->paymentAccounts()->latest()->paginate(20);

        return view('admin.payments', [
            'room' => $room,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Handle the store operation.
     */
    public function store(StorePaymentAccountRequest $request, Room $room, SavePaymentAccountAction $action, AuditService $audit): JsonResponse
    {
        $account = $action->execute($room, $request->validated());
        $audit->record('payment_account.created', 'payment_account', $account->id, $room->id, [], ['bank_code' => $account->bank_code, 'status' => $account->status]);
        return response()->json(['data' => $this->payload($account)], 201);
    }

    /**
     * Handle the update operation.
     */
    public function update(StorePaymentAccountRequest $request, Room $room, PaymentAccount $account, SavePaymentAccountAction $action, AuditService $audit): JsonResponse
    {
        abort_unless($account->room_id === $room->id, 404);
        $before = ['bank_code' => $account->bank_code, 'status' => $account->status, 'is_default' => $account->is_default];
        $account = $action->execute($room, $request->validated(), $account);
        $audit->record('payment_account.updated', 'payment_account', $account->id, $room->id, $before, ['bank_code' => $account->bank_code, 'status' => $account->status, 'is_default' => $account->is_default]);
        return response()->json(['data' => $this->payload($account)]);
    }

    /**
     * Handle the destroy operation.
     */
    public function destroy(Room $room, PaymentAccount $account, AuditService $audit): JsonResponse
    {
        abort_unless($account->room_id === $room->id, 404);
        $account->update(['status' => 'disabled', 'is_default' => false]);
        $audit->record('payment_account.disabled', 'payment_account', $account->id, $room->id, ['status' => 'active'], ['status' => 'disabled']);
        return response()->json(['data' => ['disabled' => true]]);
    }

    /**
     * Format payload.
     */
    private function payload(PaymentAccount $account): array
    {
        return [
            'id' => $account->id,
            'bank_code' => $account->bank_code,
            'bank_name' => $account->bank_name,
            'account_number' => $this->mask($account->account_number),
            'account_name' => $account->account_name,
            'is_default' => (bool) $account->is_default,
            'status' => $account->status,
        ];
    }

    /**
     * Mask account number with bullet dots.
     */
    private function mask(string $number): string
    {
        return strlen($number) <= 4 ? str_repeat('•', strlen($number)) : str_repeat('•', max(0, strlen($number) - 4)).substr($number, -4);
    }
}
