<?php

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Support\Collection;

class OrderAggregationService
{
    public function forRoom(int $roomId, ?int $campaignId = null): Collection
    {
        $orders = Order::query()->where('room_id', $roomId)->whereNotIn('status', ['cancelled'])->when($campaignId, fn ($query) => $query->where('campaign_id', $campaignId))->with('items.toppings')->get();
        return $orders->flatMap(fn ($order) => $order->items->map(function ($item) {
            $toppings = $item->toppings->map(fn ($topping) => $topping->topping_name.' x'.$topping->quantity)->implode(', ');
            return ['name' => $item->item_name, 'size' => $item->size_name, 'quantity' => (int) $item->quantity, 'toppings' => $toppings];
        }))->groupBy(fn (array $item) => implode('|', [$item['name'], $item['size'] ?? '', $item['toppings']]))->map(function (Collection $rows) {
            $first = $rows->first();
            return ['name' => $first['name'], 'size' => $first['size'], 'toppings' => $first['toppings'], 'quantity' => (int) $rows->sum('quantity')];
        })->sortBy([['name', 'asc'], ['size', 'asc']])->values();
    }
}
