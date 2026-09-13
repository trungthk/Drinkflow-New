<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\CampaignStatus;
use App\Events\OrderCreated;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\RoomUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    /**
     * Create a new order for a room user in an active campaign.
     *
     * @param Campaign $campaign Campaign entity.
     * @param RoomUser $roomUser Room user placing the order.
     * @param array<string, mixed> $data Order items and options payload.
     * @return Order Created order entity with items and toppings loaded.
     * @throws ValidationException If campaign is unavailable, user inactive, or items invalid.
     */
    public function execute(Campaign $campaign, RoomUser $roomUser, array $data): Order
    {
        $campaign->refresh();
        $roomUser->refresh();
        $roomUser->loadMissing('globalUser');
        if ($campaign->status !== CampaignStatus::Active || $campaign->room_id !== $roomUser->room_id) {
            throw ValidationException::withMessages([
                'campaign' => __('admin.campaign_unavailable'),
            ]);
        }
        if ($roomUser->status->value !== 'active' || $roomUser->globalUser->status->value !== 'active') {
            throw ValidationException::withMessages([
                'user' => __('admin.account_inactive'),
            ]);
        }

        $order = DB::transaction(function () use ($campaign, $roomUser, $data): Order {
            $items = $data['items'] ?? [];
            if ($items === []) {
                throw ValidationException::withMessages([
                    'items' => __('admin.order_must_have_items'),
                ]);
            }
            $subtotal = 0;
            $snapshots = [];
            foreach ($items as $input) {
                $item = $campaign->items()->with(['sizes', 'toppings'])->whereKey($input['item_id'] ?? 0)->first();
                $quantity = (int) ($input['quantity'] ?? 0);
                if (! $item || $item->status !== 'active' || $quantity < 1) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.item_invalid_or_sold_out'),
                    ]);
                }
                $size = ! empty($input['size_id']) ? $item->sizes->firstWhere('id', (int) $input['size_id']) : null;
                if (! empty($input['size_id']) && (! $size || $size->status !== 'active')) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.invalid_size'),
                    ]);
                }
                $toppings = collect($input['topping_ids'] ?? [])->map(fn ($id) => $item->toppings->firstWhere('id', (int) $id));
                if ($toppings->contains(fn ($t) => ! $t || $t->status !== 'active')) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.invalid_topping'),
                    ]);
                }
                $unit = (int) $item->base_price + (int) ($size?->price_delta ?? 0) + (int) $toppings->sum('price');
                $line = $unit * $quantity;
                $subtotal += $line;
                $snapshots[] = compact('item', 'size', 'toppings', 'quantity', 'unit', 'line', 'input');
            }

            $discount = (int) $campaign->discount;
            $sponsor = 0;
            $delivery = (int) $campaign->delivery_fee;
            $final = max(0, $subtotal + $delivery - $discount - $sponsor);
            $order = Order::create([
                'room_id' => $campaign->room_id,
                'campaign_id' => $campaign->id,
                'room_user_id' => $roomUser->id,
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal' => $subtotal,
                'delivery_amount' => $delivery,
                'discount_amount' => $discount,
                'sponsor_amount' => $sponsor,
                'final_amount' => $final,
                'status' => 'submitted',
                'note' => $data['note'] ?? null,
                'submitted_at' => now(),
            ]);
            foreach ($snapshots as $snapshot) {
                $orderItem = $order->items()->create([
                    'campaign_item_id' => $snapshot['item']->id,
                    'item_name' => $snapshot['item']->name,
                    'size_name' => $snapshot['size']?->name,
                    'unit_price' => $snapshot['unit'],
                    'quantity' => $snapshot['quantity'],
                    'ice_percent' => $snapshot['input']['ice_percent'] ?? null,
                    'sugar_percent' => $snapshot['input']['sugar_percent'] ?? null,
                    'line_subtotal' => $snapshot['line'],
                    'note' => $snapshot['input']['note'] ?? null,
                ]);
                foreach ($snapshot['toppings'] as $topping) {
                    $orderItem->toppings()->create([
                        'campaign_item_topping_id' => $topping->id,
                        'topping_name' => $topping->name,
                        'unit_price' => $topping->price,
                        'quantity' => 1,
                        'subtotal' => $topping->price * $snapshot['quantity'],
                    ]);
                }
            }
            return $order->load('items.toppings');
        });

        OrderCreated::dispatch($order);

        return $order;
    }
}
