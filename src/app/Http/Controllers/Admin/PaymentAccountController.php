<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\SavePaymentAccountAction;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentAccountRequest;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Services\Audit\AuditService;
use App\Services\Common\BankService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    /**
     * Handle the index operation.
     *
     * @param  Room  $room  Room entity.
     * @return JsonResponse Paginated list.
     */
    public function index(Room $room): JsonResponse
    {
        return response()->json(['data' => $room->paymentAccounts()->paginate(20)->through(fn (PaymentAccount $account) => $this->payload($account))]);
    }

    /**
     * Display the payment accounts management page.
     *
     * @param  Request     $request     Incoming request.
     * @param  Room        $room        Room entity.
     * @param  BankService $bankService Bank catalogue service.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room, BankService $bankService): View
    {
        $accounts = $room->paymentAccounts()->latest()->paginate(20);
        $banks    = $bankService->getAllBanks();

        return view('admin.payments', [
            'room'     => $room,
            'accounts' => $accounts,
            'banks'    => $banks,
        ]);
    }

    /**
     * Handle the store operation.
     *
     * @param  StorePaymentAccountRequest  $request  Validated request.
     * @param  Room                        $room     Room entity.
     * @param  SavePaymentAccountAction    $action   Persistence action.
     * @param  AuditService                $audit    Audit logger.
     * @return JsonResponse Created account payload.
     */
    public function store(StorePaymentAccountRequest $request, Room $room, SavePaymentAccountAction $action, AuditService $audit): JsonResponse
    {
        $account = $action->execute($room, $request->validated());
        $audit->record('payment_account.created', 'payment_account', $account->id, $room->id, [], ['bank_code' => $account->bank_code, 'status' => $account->status]);

        return response()->json(['data' => $this->payload($account)], 201);
    }

    /**
     * Handle the update operation.
     *
     * @param  StorePaymentAccountRequest  $request  Validated request.
     * @param  Room                        $room     Room entity.
     * @param  PaymentAccount              $account  Account to update.
     * @param  SavePaymentAccountAction    $action   Persistence action.
     * @param  AuditService                $audit    Audit logger.
     * @return JsonResponse Updated account payload.
     */
    public function update(StorePaymentAccountRequest $request, Room $room, PaymentAccount $account, SavePaymentAccountAction $action, AuditService $audit): JsonResponse
    {
        abort_unless($account->room_id === $room->id, 404);
        $before  = ['bank_code' => $account->bank_code, 'status' => $account->status, 'is_default' => $account->is_default];
        $account = $action->execute($room, $request->validated(), $account);
        $audit->record('payment_account.updated', 'payment_account', $account->id, $room->id, $before, ['bank_code' => $account->bank_code, 'status' => $account->status, 'is_default' => $account->is_default]);

        return response()->json(['data' => $this->payload($account)]);
    }

    /**
     * Handle the destroy operation — soft-disable a payment account.
     *
     * @param  Room            $room     Room entity.
     * @param  PaymentAccount  $account  Account to delete permanently.
     * @param  AuditService    $audit    Audit logger.
     * @return JsonResponse Confirmation.
     */
    public function destroy(Room $room, PaymentAccount $account, AuditService $audit): JsonResponse
    {
        abort_unless($account->room_id === $room->id, 404);

        $isUsedByLiveCampaign = $room->campaigns()
            ->where('payment_account_id', $account->id)
            ->where('status', CampaignStatus::Active->value)
            ->exists();

        if ($isUsedByLiveCampaign) {
            return response()->json([
                'message' => __('admin.payment_account_live_campaign_blocked'),
                'errors' => ['payment_account' => [__('admin.payment_account_live_campaign_blocked')]],
            ], 422);
        }

        $accountId = $account->id;
        $before = [
            'bank_code' => $account->bank_code,
            'status' => $account->status->value,
            'is_default' => $account->is_default,
        ];
        $account->delete();
        $audit->record('payment_account.deleted', 'payment_account', $accountId, $room->id, $before, []);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * Generate a locally encoded payment-details QR payload for an account.
     *
     * Returns the raw payload string so the frontend renders the QR
     * client-side with qrcode.js — no external CDN image call required.
     *
     * @param  Room            $room     Room entity (ownership check).
     * @param  PaymentAccount  $account  Account to generate QR for.
     * @param  Request         $request  Optional ?amount=&description= query params.
     * @return JsonResponse QR payload + metadata.
     */
    public function qr(Room $room, PaymentAccount $account, Request $request): JsonResponse
    {
        abort_unless($account->room_id === $room->id, 404);

        $amount      = (int) $request->query('amount', 0);
        $description = (string) $request->query('description', '');

        $payload = $this->buildLocalQrPayload($account, $amount, $description);

        return response()->json([
            'data' => [
                'payload'        => $payload,
                'bank_code'      => $account->bank_code,
                'bank_name'      => $account->bank_name,
                'account_number' => $account->getRawOriginal('account_number'),
                'account_name'   => $account->account_name,
                'amount'         => $amount,
                'description'    => $description,
            ],
        ]);
    }

    /**
     * Build a local QR payload that contains the complete payment details.
     *
     * @param  PaymentAccount  $account      Receiving payment account.
     * @param  int             $amount       Optional transfer amount.
     * @param  string          $description  Optional transfer description.
     * @return string Local QR text payload.
     */
    private function buildLocalQrPayload(PaymentAccount $account, int $amount, string $description): string
    {
        $lines = [
            'DRINKFLOW-PAYMENT',
            'BANK:' . trim($account->bank_name ?: $account->bank_code),
            'BANK_CODE:' . trim($account->bank_code),
            'ACCOUNT:' . trim($account->getRawOriginal('account_number')),
            'ACCOUNT_NAME:' . trim($account->account_name),
        ];

        if ($amount > 0) {
            $lines[] = 'AMOUNT:' . $amount;
        }

        if (trim($description) !== '') {
            $lines[] = 'DESCRIPTION:' . trim($description);
        }

        return implode("\n", $lines);
    }

    /**
     * Build standard JSON payload for a PaymentAccount model.
     *
     * @param  PaymentAccount  $account  Account to serialize.
     * @return array<string, mixed>
     */
    private function payload(PaymentAccount $account): array
    {
        return [
            'id'             => $account->id,
            'bank_code'      => $account->bank_code,
            'bank_name'      => $account->bank_name,
            'account_number' => $this->mask($account->getRawOriginal('account_number')),
            'account_name'   => $account->account_name,
            'is_default'     => (bool) $account->is_default,
            'status'         => $account->status,
        ];
    }

    /**
     * Mask all but the last 4 characters of an account number.
     *
     * @param  string  $number  Raw account number.
     * @return string           Masked string (e.g. "••••4382").
     */
    private function mask(string $number): string
    {
        return strlen($number) <= 4
            ? str_repeat('•', strlen($number))
            : str_repeat('•', max(0, strlen($number) - 4)) . substr($number, -4);
    }
}
