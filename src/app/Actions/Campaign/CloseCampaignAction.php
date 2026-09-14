<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\CampaignClosed;
use App\Models\Campaign;
use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCampaignAction
{
    /**
     * Execute the close campaign operation and optionally create debts.
     *
     * @param Campaign $campaign Campaign instance to close.
     * @param bool $allowDebt Whether to automatically create debt records for pending balances.
     * @param ?string $reason Optional reason why the campaign was closed.
     * @return Campaign Closed campaign instance.
     * @throws ValidationException If campaign is not in active or closing state.
     */
    public function execute(Campaign $campaign, bool $allowDebt = true, ?string $reason = null): Campaign
    {
        $closed = DB::transaction(function () use ($campaign, $allowDebt, $reason): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            if (! in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Closing], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_closed_invalid_state'),
                ]);
            }

            $campaign->update(['status' => CampaignStatus::Closing]);

            if ($allowDebt) {
                $orders = $campaign->orders()
                    ->whereIn('status', ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering', 'completed'])
                    ->get();

                foreach ($orders as $order) {
                    if ($order->final_amount > 0) {
                        Debt::updateOrCreate(
                            [
                                'campaign_id' => $campaign->id,
                                'room_user_id' => $order->room_user_id,
                            ],
                            [
                                'room_id' => $campaign->room_id,
                                'original_amount' => $order->final_amount,
                                'sponsor_amount' => $order->sponsor_amount,
                                'adjustment_amount' => 0,
                                'paid_amount' => 0,
                                'remaining_amount' => $order->final_amount,
                                'status' => 'unpaid',
                            ]
                        );
                    }
                }
            }

            $campaign->update([
                'status' => CampaignStatus::Closed,
                'closed_at' => now(),
            ]);

            app(AuditService::class)->record(
                'campaign.closed',
                'campaign',
                $campaign->id,
                $campaign->room_id,
                ['status' => CampaignStatus::Active->value],
                ['status' => CampaignStatus::Closed->value, 'allow_debt' => $allowDebt, 'reason' => $reason]
            );

            return $campaign->fresh();
        });

        CampaignClosed::dispatch($closed->load('room'));

        return $closed;
    }
}

