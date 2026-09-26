<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignItemStatus;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentAccountStatus;
use App\Events\CampaignCreated;
use App\Events\CampaignCancelled;
use App\Models\Campaign;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionCampaignAction
{
    /**
     * Create the action.
     *
     * @param EnsureNoRunningCampaignAction $ensureNoRunningCampaign Guard keeping a single running campaign per room.
     * @return void
     */
    public function __construct(private readonly EnsureNoRunningCampaignAction $ensureNoRunningCampaign)
    {
    }

    /**
     * Activate a campaign from draft/scheduled state.
     *
     * @param Campaign $campaign Campaign instance to activate.
     * @return Campaign Activated campaign instance.
     * @throws ValidationException If campaign state, deadline, items or payment account is invalid.
     */
    public function activate(Campaign $campaign): Campaign
    {
        $updated = DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->with('items')->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_cannot_activate_state'),
                ]);
            }
            $this->ensureNoRunningCampaign->execute($campaign->room_id, $campaign->id);
            if ($campaign->deadline && $campaign->deadline->isPast()) {
                throw ValidationException::withMessages([
                    'deadline' => __('admin.deadline_must_be_future'),
                ]);
            }
            if (! $campaign->items->contains(fn ($item) => $item->status === CampaignItemStatus::Active)) {
                throw ValidationException::withMessages([
                    'items' => __('admin.campaign_must_have_items'),
                ]);
            }
            if (! $campaign->payment_account_id || ! PaymentAccount::query()->whereKey($campaign->payment_account_id)->where('room_id', $campaign->room_id)->where('status', PaymentAccountStatus::Active)->exists()) {
                throw ValidationException::withMessages([
                    'payment_account_id' => __('admin.campaign_requires_payment_account'),
                ]);
            }

            $campaign->update(['status' => CampaignStatus::Active, 'started_at' => now()]);

            return $campaign->fresh(['room', 'items']);
        });

        CampaignCreated::dispatch($updated);

        return $updated;
    }

    /**
     * Cancel an active or pending campaign and all its active orders.
     *
     * @param Campaign $campaign Campaign instance to cancel.
     * @return Campaign Cancelled campaign instance.
     * @throws ValidationException If campaign cannot be cancelled from its current state.
     */
    public function cancel(Campaign $campaign): Campaign
    {
        $cancelled = DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled, CampaignStatus::Active, CampaignStatus::Closing], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_cannot_cancel_state'),
                ]);
            }

            $isLive = in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Scheduled, CampaignStatus::Closing], true);

            $ordersToCancel = $campaign->orders()
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->get();

            $now = now();
            /** @var \App\Models\Order $order */
            foreach ($ordersToCancel as $order) {
                $previousStatus = $order->status->value;
                $order->update([
                    'status' => OrderStatus::Cancelled,
                    'cancelled_at' => $now,
                ]);

                app(\App\Services\Audit\AuditService::class)->record(
                    'order.status_updated',
                    'order',
                    $order->id,
                    $order->room_id,
                    ['status' => $previousStatus],
                    ['status' => OrderStatus::Cancelled->value]
                );

                \App\Events\OrderUpdated::dispatch($order->fresh(), $previousStatus);
            }

            // Live campaigns become archived, while drafts become cancelled
            $newStatus = $isLive ? CampaignStatus::Archived : CampaignStatus::Cancelled;
            $campaign->update(['status' => $newStatus]);

            return $campaign->fresh();
        });

        CampaignCancelled::dispatch($cancelled->load('room'));

        return $cancelled;
    }

    /**
     * Archive a closed or cancelled campaign.
     *
     * @param Campaign $campaign Campaign instance to archive.
     * @return Campaign Archived campaign instance.
     * @throws ValidationException If campaign is not closed or cancelled.
     */
    public function archive(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Closed, CampaignStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_cannot_archive_state'),
                ]);
            }
            $campaign->update(['status' => CampaignStatus::Archived]);

            return $campaign->fresh();
        });
    }
}
