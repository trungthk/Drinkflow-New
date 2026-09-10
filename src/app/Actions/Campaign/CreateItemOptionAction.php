<?php

namespace App\Actions\Campaign;

use App\Models\CampaignItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateItemOptionAction
{
    public function topping(CampaignItem $item, array $data): object
    {
        if ($item->status !== 'active') {
            throw ValidationException::withMessages(['item' => 'Món không khả dụng.']);
        }

        return DB::transaction(fn() => $item->toppings()->create(['name' => $data['name'], 'price' => $data['price'] ?? 0, 'status' => $data['status'] ?? 'active', 'sort_order' => $data['sort_order'] ?? 0]));
    }

    public function size(CampaignItem $item, array $data): object
    {
        if ($item->status !== 'active') {
            throw ValidationException::withMessages(['item' => 'Món không khả dụng.']);
        }

        return DB::transaction(fn() => $item->sizes()->create(['name' => $data['name'], 'price_delta' => $data['price_delta'] ?? 0, 'status' => $data['status'] ?? 'active', 'sort_order' => $data['sort_order'] ?? 0]));
    }
}
