<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignItemStatus;
use App\Models\CampaignItem;
use App\Models\CampaignItemSize;
use App\Models\CampaignItemTopping;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateItemOptionAction
{
    /**
     * Create a new topping option for a campaign menu item.
     *
     * @param CampaignItem $item Parent campaign item entity.
     * @param array<string, mixed> $data Topping configuration data.
     * @return CampaignItemTopping Created topping entity.
     * @throws ValidationException If parent item is not active.
     */
    public function topping(CampaignItem $item, array $data): CampaignItemTopping
    {
        if ($item->status !== CampaignItemStatus::Active) {
            throw ValidationException::withMessages([
                'item' => __('admin.item_not_available'),
            ]);
        }

        return DB::transaction(fn (): CampaignItemTopping => $item->toppings()->create([
            'name' => $data['name'],
            'price' => $data['price'] ?? 0,
            'status' => $data['status'] ?? CampaignItemStatus::Active->value,
            'sort_order' => $data['sort_order'] ?? 0,
        ]));
    }

    /**
     * Create a new size option for a campaign menu item.
     *
     * @param CampaignItem $item Parent campaign item entity.
     * @param array<string, mixed> $data Size configuration data.
     * @return CampaignItemSize Created size entity.
     * @throws ValidationException If parent item is not active.
     */
    public function size(CampaignItem $item, array $data): CampaignItemSize
    {
        if ($item->status !== CampaignItemStatus::Active) {
            throw ValidationException::withMessages([
                'item' => __('admin.item_not_available'),
            ]);
        }

        return DB::transaction(fn (): CampaignItemSize => $item->sizes()->create([
            'name' => $data['name'],
            'price_delta' => $data['price_delta'] ?? 0,
            'status' => $data['status'] ?? CampaignItemStatus::Active->value,
            'sort_order' => $data['sort_order'] ?? 0,
        ]));
    }
}
