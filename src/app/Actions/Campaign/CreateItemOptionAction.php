<?php

namespace App\Actions\Campaign;

use App\Models\CampaignItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateItemOptionAction
{
    /**
     * Handle the topping operation.
     * @param CampaignItem $item Parameter value.
     * @param array $data Parameter value.
     * @return object Result of the operation.
     */
    public function topping(CampaignItem $item, array $data): object
    {
        if ($item->status !== 'active') {
            throw ValidationException::withMessages(['item' => 'MĂ³n khĂ´ng kháº£ dá»¥ng.']);
        }

        return DB::transaction(fn() => $item->toppings()->create(['name' => $data['name'], 'price' => $data['price'] ?? 0, 'status' => $data['status'] ?? 'active', 'sort_order' => $data['sort_order'] ?? 0]));
    }

    /**
     * Handle the size operation.
     * @param CampaignItem $item Parameter value.
     * @param array $data Parameter value.
     * @return object Result of the operation.
     */
    public function size(CampaignItem $item, array $data): object
    {
        if ($item->status !== 'active') {
            throw ValidationException::withMessages(['item' => 'MĂ³n khĂ´ng kháº£ dá»¥ng.']);
        }

        return DB::transaction(fn() => $item->sizes()->create(['name' => $data['name'], 'price_delta' => $data['price_delta'] ?? 0, 'status' => $data['status'] ?? 'active', 'sort_order' => $data['sort_order'] ?? 0]));
    }
}
