<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentAccountStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for "which bank account receives this payment".
 *
 * A campaign order/debt is paid only to that campaign's own active account, never to another
 * room account. Debts without a campaign and the pay-all request use the room's active account.
 * Members may only report a payment ("I have paid") when a receiving account exists.
 */
class ReceivingAccountResolver
{
    /**
     * Resolve the receiving account of a campaign.
     *
     * @param Campaign|null $campaign Campaign being paid for.
     * @return PaymentAccount|null The campaign's account when it is active, otherwise null.
     */
    public function forCampaign(?Campaign $campaign): ?PaymentAccount
    {
        $account = $campaign?->paymentAccount;

        return $account?->status === PaymentAccountStatus::Active ? $account : null;
    }

    /**
     * Resolve the room-level receiving account used by pay-all and debts without a campaign.
     *
     * @param Room $room Current room.
     * @return PaymentAccount|null First active account of the room, or null when none exists.
     */
    public function forRoom(Room $room): ?PaymentAccount
    {
        return $room->paymentAccounts()->where('status', PaymentAccountStatus::Active)->first();
    }

    /**
     * Resolve the receiving account of one debt.
     *
     * @param Debt $debt Debt being paid.
     * @param Room $room Room owning the debt.
     * @return PaymentAccount|null Campaign account for campaign debts, room account otherwise.
     */
    public function forDebt(Debt $debt, Room $room): ?PaymentAccount
    {
        return $debt->campaign_id !== null ? $this->forCampaign($debt->campaign) : $this->forRoom($room);
    }

    /**
     * Reject a payment report when there is no account to receive the money.
     *
     * @param PaymentAccount|null $account Resolved receiving account.
     * @param string $field Validation error key.
     * @return void
     * @throws ValidationException When the account is missing.
     */
    public function ensureConfigured(?PaymentAccount $account, string $field): void
    {
        if ($account === null) {
            throw ValidationException::withMessages([
                $field => __('room.debts.payment_account_not_configured'),
            ]);
        }
    }
}
