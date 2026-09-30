<?php

declare(strict_types=1);

namespace App\Http\Resources\Member;

use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A member's own order (and the orders placed for others under it), with an explicit field list.
 *
 * Proxy (child) orders only expose the recipient's display name and member code.
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request Incoming request.
     * @return array<string, mixed> Order fields.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'campaign_id' => $this->campaign_id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal' => $this->subtotal,
            'delivery_amount' => $this->delivery_amount,
            'discount_amount' => $this->discount_amount,
            'sponsor_amount' => $this->sponsor_amount,
            'final_amount' => $this->final_amount,
            'note' => $this->note,
            'submitted_at' => $this->submitted_at,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(static fn (OrderItem $item): array => [
                'id' => $item->id,
                'campaign_item_id' => $item->campaign_item_id,
                'item_name' => $item->item_name,
                'size_name' => $item->size_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'ice_percent' => $item->ice_percent,
                'sugar_percent' => $item->sugar_percent,
                'line_subtotal' => $item->line_subtotal,
                'note' => $item->note,
                'is_self_paid' => $item->is_self_paid,
                'toppings' => $item->relationLoaded('toppings') ? $item->toppings->map(static fn (OrderItemTopping $topping): array => [
                    'topping_name' => $topping->topping_name,
                    'unit_price' => $topping->unit_price,
                    'quantity' => $topping->quantity,
                    'subtotal' => $topping->subtotal,
                ])->values()->all() : [],
            ])->values()->all()),
            'recipient' => $this->when($this->parent_id !== null && $this->relationLoaded('roomUser'), fn (): array => [
                'display_name' => $this->roomUser?->payerName(),
                'user_code' => $this->roomUser?->user_code,
            ]),
            'children' => self::collection($this->whenLoaded('children')),
            'campaign' => new CampaignSummaryResource($this->whenLoaded('campaign')),
            'room' => new RoomResource($this->whenLoaded('room')),
        ];
    }
}
