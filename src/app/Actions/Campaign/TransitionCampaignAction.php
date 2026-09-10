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
    /**
     * Handle the activate operation.
     * @param Campaign $campaign Parameter value.
     * @return Campaign Result of the operation.
     */
    public function activate(Campaign $campaign): Campaign
    {
        $updated = DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->with('items')->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled], true)) {
                throw ValidationException::withMessages(['campaign' => 'Campaign khĂ´ng thá»ƒ má»Ÿ á»Ÿ tráº¡ng thĂ¡i hiá»‡n táº¡i.']);
            }
            if ($campaign->deadline && $campaign->deadline->isPast()) {
                throw ValidationException::withMessages(['deadline' => 'Deadline pháº£i náº±m trong tÆ°Æ¡ng lai.']);
            }
            if (! $campaign->items->contains(fn ($item) => $item->status === 'active')) {
                throw ValidationException::withMessages(['items' => 'Campaign pháº£i cĂ³ Ă­t nháº¥t má»™t mĂ³n Ä‘ang bĂ¡n.']);
            }
            if (! $campaign->payment_account_id || ! PaymentAccount::query()->whereKey($campaign->payment_account_id)->where('room_id', $campaign->room_id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['payment_account_id' => 'Campaign pháº£i cĂ³ tĂ i khoáº£n thanh toĂ¡n active cá»§a Room.']);
            }

            $campaign->update(['status' => CampaignStatus::Active, 'started_at' => now()]);

            return $campaign->fresh(['room', 'items']);
        });

        CampaignCreated::dispatch($updated);

        return $updated;
    }

    /**
     * Handle the cancel operation.
     * @param Campaign $campaign Parameter value.
     * @return Campaign Result of the operation.
     */
    public function cancel(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled, CampaignStatus::Active], true)) {
                throw ValidationException::withMessages(['campaign' => 'Campaign khĂ´ng thá»ƒ há»§y á»Ÿ tráº¡ng thĂ¡i hiá»‡n táº¡i.']);
            }
            if ($campaign->orders()->whereIn('status', ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering'])->exists()) {
                throw ValidationException::withMessages(['campaign' => 'KhĂ´ng thá»ƒ há»§y khi cĂ²n order Ä‘ang hoáº¡t Ä‘á»™ng.']);
            }
            $campaign->update(['status' => CampaignStatus::Cancelled]);

            return $campaign->fresh();
        });
    }

    /**
     * Handle the archive operation.
     * @param Campaign $campaign Parameter value.
     * @return Campaign Result of the operation.
     */
    public function archive(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, [CampaignStatus::Closed, CampaignStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['campaign' => 'Chá»‰ archive campaign Ä‘Ă£ Ä‘Ă³ng hoáº·c Ä‘Ă£ há»§y.']);
            }
            $campaign->update(['status' => CampaignStatus::Archived]);

            return $campaign->fresh();
        });
    }
}
