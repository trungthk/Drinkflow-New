<?php

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\CampaignCreated;
use App\Models\Campaign;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionCampaignAction
{
    public function activate(Campaign $campaign): Campaign
    {
        $updated = DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->with('items')->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled], true)) {
                throw ValidationException::withMessages(['campaign' => 'Campaign không thể mở ở trạng thái hiện tại.']);
            }
            if ($campaign->deadline && $campaign->deadline->isPast()) {
                throw ValidationException::withMessages(['deadline' => 'Deadline phải nằm trong tương lai.']);
            }
            if (! $campaign->items->contains(fn ($item) => $item->status === 'active')) {
                throw ValidationException::withMessages(['items' => 'Campaign phải có ít nhất một món đang bán.']);
            }
            if (! $campaign->payment_account_id || ! PaymentAccount::query()->whereKey($campaign->payment_account_id)->where('room_id', $campaign->room_id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['payment_account_id' => 'Campaign phải có tài khoản thanh toán active của Room.']);
            }

            $campaign->update(['status' => CampaignStatus::Active, 'started_at' => now()]);

            return $campaign->fresh(['room', 'items']);
        });

        CampaignCreated::dispatch($updated);

        return $updated;
    }

    public function cancel(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled, CampaignStatus::Active], true)) {
                throw ValidationException::withMessages(['campaign' => 'Campaign không thể hủy ở trạng thái hiện tại.']);
            }
            if ($campaign->orders()->whereIn('status', ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering'])->exists()) {
                throw ValidationException::withMessages(['campaign' => 'Không thể hủy khi còn order đang hoạt động.']);
            }
            $campaign->update(['status' => CampaignStatus::Cancelled]);

            return $campaign->fresh();
        });
    }

    public function archive(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Closed, CampaignStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['campaign' => 'Chỉ archive campaign đã đóng hoặc đã hủy.']);
            }
            $campaign->update(['status' => CampaignStatus::Archived]);

            return $campaign->fresh();
        });
    }
}
