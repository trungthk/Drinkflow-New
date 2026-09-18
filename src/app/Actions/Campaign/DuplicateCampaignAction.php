<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class DuplicateCampaignAction
{
    /**
     * Handle the execute operation.
     * @param Campaign $campaign Parameter value.
     * @param int $adminId Parameter value.
     * @return Campaign Result of the operation.
     */
    public function execute(Campaign $campaign, int $adminId): Campaign
    {
        return DB::transaction(function () use ($campaign, $adminId): Campaign {
            $campaign->load(['items.sizes', 'items.toppings']);
            $copy = $campaign->replicate(['status', 'started_at', 'closed_at']);
            $copy->status = CampaignStatus::Draft;
            $copy->started_at = null;
            $copy->closed_at = null;
            $copy->name = $campaign->name.' (copy)';
            $copy->creator_admin_id = $adminId;
            $copy->save();

            foreach ($campaign->items as $item) {
                $itemCopy = $item->replicate(['campaign_id']);
                $itemCopy->campaign_id = $copy->id;
                $itemCopy->save();
                foreach ($item->sizes as $size) {
                    $sizeCopy = $size->replicate(['campaign_item_id']);
                    $sizeCopy->campaign_item_id = $itemCopy->id;
                    $sizeCopy->save();
                }
                foreach ($item->toppings as $topping) {
                    $toppingCopy = $topping->replicate(['campaign_item_id']);
                    $toppingCopy->campaign_item_id = $itemCopy->id;
                    $toppingCopy->save();
                }
            }

            return $copy->fresh(['items.sizes', 'items.toppings']);
        });
    }
}
