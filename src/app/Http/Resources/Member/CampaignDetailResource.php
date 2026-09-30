<?php

declare(strict_types=1);

namespace App\Http\Resources\Member;

use App\Models\CampaignItem;
use App\Models\CampaignItemSize;
use App\Models\CampaignItemTopping;
use Illuminate\Http\Request;

/**
 * Campaign with its orderable menu, as members see it (no room settings, creator or allocations).
 *
 * @mixin \App\Models\Campaign
 */
class CampaignDetailResource extends CampaignSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request Incoming request.
     * @return array<string, mixed> Campaign summary plus menu items.
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'description' => $this->description,
            'flat_price' => $this->flat_price,
            'room' => new RoomResource($this->whenLoaded('room')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(static fn (CampaignItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'description' => $item->description,
                'image_url' => $item->image_url,
                'base_price' => $item->base_price,
                'sponsor_amount' => $item->sponsor_amount,
                'status' => $item->status,
                'sizes' => $item->relationLoaded('sizes') ? $item->sizes->map(static fn (CampaignItemSize $size): array => [
                    'id' => $size->id, 'name' => $size->name, 'price_delta' => $size->price_delta, 'status' => $size->status,
                ])->values()->all() : [],
                'toppings' => $item->relationLoaded('toppings') ? $item->toppings->map(static fn (CampaignItemTopping $topping): array => [
                    'id' => $topping->id, 'name' => $topping->name, 'price' => $topping->price, 'status' => $topping->status,
                ])->values()->all() : [],
            ])->values()->all()),
        ];
    }
}
