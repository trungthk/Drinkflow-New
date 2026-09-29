<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\CampaignItemStatus;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Debt\DebtCreditService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Validation\ValidationException;

/**
 * Prices order lines against a campaign menu and applies the shared order rules (per-line budget cap,
 * sponsorship, personal debt ceiling). Used both when an order is created and when an admin edits its items.
 */
class OrderPricingService
{
    public function __construct(private readonly DebtCreditService $credit) {}

    /**
     * Validate requested lines against the campaign's active menu and price them.
     *
     * @param Campaign $campaign Campaign (ideally locked) whose menu is ordered from.
     * @param array<int, array<string, mixed>> $items Requested lines (item_id, quantity, size_id, topping_ids, note, is_self_paid...).
     * @return array{lines: array<int, array<string, mixed>>, subtotal: int} Priced line snapshots and their subtotal.
     * @throws ValidationException When an item, size or topping is unavailable, or a line exceeds the per-product cap.
     */
    public function priceLines(Campaign $campaign, array $items): array
    {
        $subtotal = 0;
        $lines = [];
        foreach ($items as $input) {
            $item = $campaign->items()->with(['sizes', 'toppings'])->whereKey($input['item_id'] ?? 0)->first();
            $quantity = (int) ($input['quantity'] ?? 0);
            if (! $item || ! $this->isActive($item->status) || $quantity < 1) {
                throw ValidationException::withMessages([
                    'items' => __('admin.item_invalid_or_sold_out'),
                ]);
            }
            $size = ! empty($input['size_id']) ? $item->sizes->firstWhere('id', (int) $input['size_id']) : null;
            if (! empty($input['size_id']) && (! $size || ! $this->isActive($size->status))) {
                throw ValidationException::withMessages([
                    'items' => __('admin.invalid_size'),
                ]);
            }
            $toppings = collect($input['topping_ids'] ?? [])->map(fn ($id) => $item->toppings->firstWhere('id', (int) $id));
            if ($toppings->contains(fn ($topping): bool => ! $topping || ! $this->isActive($topping->status))) {
                throw ValidationException::withMessages([
                    'items' => __('admin.invalid_topping'),
                ]);
            }
            $unit = (int) $item->base_price + (int) ($size?->price_delta ?? 0) + (int) $toppings->sum('price');
            $line = $unit * $quantity;
            // The per-product cap applies to the whole line: unit price × quantity.
            if ((int) $campaign->max_budget > 0 && $line > (int) $campaign->max_budget) {
                throw ValidationException::withMessages([
                    'items' => __('admin.item_budget_limit_exceeded', [
                        'limit' => FormatHelper::formatCurrency((int) $campaign->max_budget),
                    ]),
                ]);
            }
            $subtotal += $line;
            $isSelfPaid = filter_var($input['is_self_paid'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $lines[] = compact('item', 'size', 'toppings', 'quantity', 'unit', 'line', 'input', 'isSelfPaid');
        }

        return ['lines' => $lines, 'subtotal' => $subtotal];
    }

    /**
     * Calculate sponsorship while the campaign row is locked to protect its shared budget.
     *
     * Items marked "trả riêng" (self-paid) never receive sponsor coverage: their amount
     * is excluded from the sponsorable charge so the ordering member is billed directly.
     *
     * @param Campaign $campaign Locked campaign.
     * @param array<int, array<string, mixed>> $lines Priced lines from priceLines().
     * @param int $subtotal Item subtotal (includes self-paid items).
     * @param int $delivery Delivery charge.
     * @param int $discount Campaign discount.
     * @param int|null $excludeOrderId Order being re-priced, whose current sponsorship is not counted as used budget.
     * @return int Sponsor amount for the order.
     */
    public function sponsorAmount(Campaign $campaign, array $lines, int $subtotal, int $delivery, int $discount, ?int $excludeOrderId = null): int
    {
        $selfPaidSubtotal = (int) collect($lines)->sum(
            static fn (array $line): int => $line['isSelfPaid'] ? (int) $line['line'] : 0,
        );
        $sponsorableSubtotal = max(0, $subtotal - $selfPaidSubtotal);
        $charge = max(0, $sponsorableSubtotal + $delivery - $discount);
        $configuredPerItem = (int) collect($lines)->sum(
            static fn (array $line): int => $line['isSelfPaid'] ? 0 : (int) $line['item']->sponsor_amount * (int) $line['quantity'],
        );

        return match ($campaign->sponsor_type) {
            Campaign::SPONSOR_TYPE_FULL => $charge,
            Campaign::SPONSOR_TYPE_PER_ITEM => min($charge, $configuredPerItem),
            Campaign::SPONSOR_TYPE_BUDGET => min($charge, max(0, (int) $campaign->max_budget - (int) $campaign->orders()
                ->when($excludeOrderId !== null, static fn ($query) => $query->whereKeyNot($excludeOrderId))
                ->sum('sponsor_amount'))),
            default => 0,
        };
    }

    /**
     * Enforce the room's personal debt ceiling when automatic locking is enabled.
     *
     * Credit in use counts every outstanding campaign debt, including debts waiting in a payment
     * request. The member row is locked so concurrent orders and debt bundling are serialized.
     *
     * @param RoomUser $roomUser Ordering room member.
     * @param int $orderAmount Net amount of the order.
     * @return void
     * @throws ValidationException When the order would exceed the configured ceiling.
     */
    public function enforceDebtPolicy(RoomUser $roomUser, int $orderAmount): void
    {
        /** @var Room $room */
        $room = $roomUser->room()->firstOrFail();
        $policy = $this->credit->policy($room);
        if (! $policy['enabled']) {
            return;
        }
        RoomUser::query()->whereKey($roomUser->id)->lockForUpdate()->first();
        if ($this->credit->used($roomUser) + $orderAmount > $policy['ceiling']) {
            throw ValidationException::withMessages([
                'order' => __('admin.debt_limit_reached', ['limit' => FormatHelper::formatCurrency($policy['ceiling'])]),
            ]);
        }
    }

    /**
     * Persist priced lines (and their toppings) as the order's items.
     *
     * @param Order $order Order receiving the items.
     * @param array<int, array<string, mixed>> $lines Priced lines from priceLines().
     * @return void
     */
    public function createItems(Order $order, array $lines): void
    {
        foreach ($lines as $line) {
            $orderItem = $order->items()->create([
                'campaign_item_id' => $line['item']->id,
                'item_name' => $line['item']->name,
                'size_name' => $line['size']?->name,
                'unit_price' => $line['unit'],
                'quantity' => $line['quantity'],
                'ice_percent' => $line['input']['ice_percent'] ?? null,
                'sugar_percent' => $line['input']['sugar_percent'] ?? null,
                'line_subtotal' => $line['line'],
                'note' => $line['input']['note'] ?? null,
                'is_self_paid' => $line['isSelfPaid'],
            ]);
            foreach ($line['toppings'] as $topping) {
                $orderItem->toppings()->create([
                    'campaign_item_topping_id' => $topping->id,
                    'topping_name' => $topping->name,
                    'unit_price' => $topping->price,
                    'quantity' => 1,
                    'subtotal' => $topping->price * $line['quantity'],
                ]);
            }
        }
    }

    /**
     * Whether a menu entry status (enum or raw string) is active.
     *
     * @param \BackedEnum|string|null $status Item, size or topping status.
     * @return bool True when the entry can be ordered.
     */
    private function isActive(\BackedEnum|string|null $status): bool
    {
        $value = $status instanceof \BackedEnum ? $status->value : (string) ($status ?? '');

        return $value === CampaignItemStatus::Active->value;
    }
}
