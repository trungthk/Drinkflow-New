<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\PaymentAccountStatus;
use App\Models\Campaign;
use App\Models\PaymentAccount;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateClosedCampaignPaymentAccountAction
{
    /**
     * Create the action instance.
     *
     * @param AuditService $auditService Audit logger service.
     */
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Switch the receiving bank account of a closed campaign so members pay debts to the right account.
     *
     * Only the payment account changes; orders, debts and the rest of the locked campaign stay untouched.
     *
     * @param Campaign $campaign Closed (or archived) campaign to adjust.
     * @param int $paymentAccountId Active payment account of the same room.
     * @param int|null $adminId Admin performing the change.
     * @return Campaign Fresh campaign with its payment account loaded.
     * @throws ValidationException When the campaign is not closed or the account is not usable for its room.
     */
    public function execute(Campaign $campaign, int $paymentAccountId, ?int $adminId = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $paymentAccountId, $adminId): Campaign {
            $locked = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if (! $locked->isLocked()) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_payment_account_closed_only'),
                ]);
            }

            $accountExists = PaymentAccount::query()
                ->whereKey($paymentAccountId)
                ->where('room_id', $locked->room_id)
                ->where('status', PaymentAccountStatus::Active)
                ->exists();
            if (! $accountExists) {
                throw ValidationException::withMessages([
                    'payment_account_id' => __('admin.invalid_payment_account'),
                ]);
            }

            $before = $locked->payment_account_id;
            $locked->update(['payment_account_id' => $paymentAccountId]);

            $this->auditService->record(
                'campaign.payment_account_updated',
                'campaign',
                $locked->id,
                $locked->room_id,
                ['payment_account_id' => $before],
                ['payment_account_id' => $paymentAccountId],
                ['admin_id' => $adminId]
            );

            return $locked->fresh(['paymentAccount']);
        });
    }
}
