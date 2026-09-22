<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\CampaignItemStatus;
use App\Enums\DebtStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomUserStatus;
use App\Events\OrderCreated;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\RoomUser;
use App\Support\Helpers\FormatHelper;
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
     * @param int|null $parentId Parent order ID when creating a child (proxy) order.
     * @param bool $allowEmpty Whether an empty placeholder parent order is allowed.
     * @return Order Created order entity with items and toppings loaded.
     * @throws ValidationException If campaign is unavailable, user inactive, or items invalid.
     */
    public function execute(Campaign $campaign, RoomUser $roomUser, array $data, ?int $parentId = null, bool $allowEmpty = false): Order
    {
        $campaign->refresh();
        $roomUser->refresh();
        $roomUser->loadMissing('globalUser');
        if ($campaign->room_id !== $roomUser->room_id) {
            throw ValidationException::withMessages([
                'campaign' => __('admin.campaign_unavailable'),
            ]);
        }
        $this->ensureCampaignIsOrderable($campaign);
        if ($roomUser->status !== RoomUserStatus::Active || $roomUser->globalUser->status !== GlobalUserStatus::Active) {
            throw ValidationException::withMessages([
                'user' => __('admin.account_inactive'),
            ]);
        }

        $order = DB::transaction(function () use ($campaign, $roomUser, $data, $parentId): Order {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            // Serialize daily order numbering across campaigns in the same room.
            \App\Models\Room::query()->whereKey($campaign->room_id)->lockForUpdate()->firstOrFail();
            $this->ensureCampaignIsOrderable($campaign);
            $items = $data['items'] ?? [];
            if ($items === [] && ! $allowEmpty) {
                throw ValidationException::withMessages([
                    'items' => __('admin.order_must_have_items'),
                ]);
            }
            $subtotal = 0;
            $snapshots = [];
            foreach ($items as $input) {
                $item = $campaign->items()->with(['sizes', 'toppings'])->whereKey($input['item_id'] ?? 0)->first();
                $quantity = (int) ($input['quantity'] ?? 0);
                $itemStatus = $item?->status instanceof \BackedEnum ? $item->status->value : (string) ($item?->status ?? '');
                if (! $item || $itemStatus !== CampaignItemStatus::Active->value || $quantity < 1) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.item_invalid_or_sold_out'),
                    ]);
                }
                $size = ! empty($input['size_id']) ? $item->sizes->firstWhere('id', (int) $input['size_id']) : null;
                $sizeStatus = $size?->status instanceof \BackedEnum ? $size->status->value : (string) ($size?->status ?? '');
                if (! empty($input['size_id']) && (! $size || $sizeStatus !== CampaignItemStatus::Active->value)) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.invalid_size'),
                    ]);
                }
                $toppings = collect($input['topping_ids'] ?? [])->map(fn ($id) => $item->toppings->firstWhere('id', (int) $id));
                if ($toppings->contains(function ($t) {
                    if (! $t) return true;
                    $tStatus = $t->status instanceof \BackedEnum ? $t->status->value : (string) $t->status;
                    return $tStatus !== CampaignItemStatus::Active->value;
                })) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.invalid_topping'),
                    ]);
                }
                $unit = (int) $item->base_price + (int) ($size?->price_delta ?? 0) + (int) $toppings->sum('price');
                if ((int) $campaign->max_budget > 0 && $unit > (int) $campaign->max_budget) {
                    throw ValidationException::withMessages([
                        'items' => __('admin.item_budget_limit_exceeded', [
                            'limit' => FormatHelper::formatCurrency((int) $campaign->max_budget),
                        ]),
                    ]);
                }
                $line = $unit * $quantity;
                $subtotal += $line;
                $isSelfPaid = filter_var($input['is_self_paid'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $snapshots[] = compact('item', 'size', 'toppings', 'quantity', 'unit', 'line', 'input', 'isSelfPaid');
            }

            $discount = 0;
            $delivery = 0;
            $sponsor = $this->sponsorAmount($campaign, $snapshots, $subtotal, $delivery, $discount);
            $final = max(0, $subtotal + $delivery - $discount - $sponsor);
            $this->enforceDebtPolicy($roomUser, $final);
            $order = Order::create([
                'parent_id'       => $parentId,
                'room_id'         => $campaign->room_id,
                'campaign_id'     => $campaign->id,
                'room_user_id'    => $roomUser->id,
                'payment_method'  => $data['payment_method'] ?? null,
                'subtotal'        => $subtotal,
                'delivery_amount' => $delivery,
                'discount_amount' => $discount,
                'sponsor_amount'  => $sponsor,
                'final_amount'    => $final,
                'status'          => OrderStatus::Submitted,
                'note'            => $data['note'] ?? null,
                'submitted_at'    => now(),
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
                    'is_self_paid' => $snapshot['isSelfPaid'],
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

    /**
     * Ensure that a campaign still accepts orders at the current time.
     *
     * @param Campaign $campaign Campaign being ordered from.
     * @return void
     * @throws ValidationException When the campaign is not live or its deadline has passed.
     */
    private function ensureCampaignIsOrderable(Campaign $campaign): void
    {
        if (! $campaign->isOrderable()) {
            throw ValidationException::withMessages([
                'campaign' => __('room.campaign.ordering_closed'),
            ]);
        }
    }

    /**
     * Enforce the room's personal debt ceiling when automatic locking is enabled.
     *
     * @param RoomUser $roomUser Ordering room member.
     * @param int $orderAmount Net amount of the new order.
     * @return void
     * @throws ValidationException When the order would exceed the configured ceiling.
     */
    private function enforceDebtPolicy(RoomUser $roomUser, int $orderAmount): void
    {
        /** @var \App\Models\Room $room */
        $room = $roomUser->room()->firstOrFail();
        $settings = $room->roomSettings()
            ->whereIn('key', ['personal_debt_ceiling', 'auto_lock_on_debt_limit'])
            ->get()
            ->keyBy('key');
        $autoLock = filter_var($settings->get('auto_lock_on_debt_limit')?->value ?? true, FILTER_VALIDATE_BOOLEAN);
        if (! $autoLock) {
            return;
        }
        $ceiling = (int) ($settings->get('personal_debt_ceiling')?->value ?? 150000);
        $outstanding = (int) $roomUser->debts()->whereIn('status', [DebtStatus::Unpaid->value, DebtStatus::Partial->value])->sum('remaining_amount');
        if ($outstanding + $orderAmount > $ceiling) {
            throw ValidationException::withMessages([
                'order' => __('admin.debt_limit_reached', ['limit' => FormatHelper::formatCurrency($ceiling)]),
            ]);
        }
    }

    /**
     * Calculate sponsorship while the campaign row is locked to protect its shared budget.
     *
     * Items marked "trả riêng" (self-paid) never receive sponsor coverage: their amount
     * is excluded from the sponsorable charge so the ordering member is billed directly.
     *
     * @param Campaign $campaign Locked campaign.
     * @param array<int, array<string, mixed>> $snapshots Ordered item snapshots.
     * @param int $subtotal Item subtotal (includes self-paid items).
     * @param int $delivery Delivery charge.
     * @param int $discount Campaign discount.
     * @return int Sponsor amount for the order.
     */
    private function sponsorAmount(Campaign $campaign, array $snapshots, int $subtotal, int $delivery, int $discount): int
    {
        $selfPaidSubtotal = (int) collect($snapshots)->sum(
            static fn (array $snapshot): int => $snapshot['isSelfPaid'] ? (int) $snapshot['line'] : 0,
        );
        $sponsorableSubtotal = max(0, $subtotal - $selfPaidSubtotal);
        $charge = max(0, $sponsorableSubtotal + $delivery - $discount);
        $configuredPerItem = (int) collect($snapshots)->sum(
            static fn (array $snapshot): int => $snapshot['isSelfPaid'] ? 0 : (int) $snapshot['item']->sponsor_amount * (int) $snapshot['quantity'],
        );

        return match ($campaign->sponsor_type) {
            Campaign::SPONSOR_TYPE_FULL => $charge,
            Campaign::SPONSOR_TYPE_PER_ITEM => min($charge, $configuredPerItem),
            Campaign::SPONSOR_TYPE_BUDGET => min($charge, max(0, (int) $campaign->max_budget - (int) $campaign->orders()->sum('sponsor_amount'))),
            default => 0,
        };
    }
}
