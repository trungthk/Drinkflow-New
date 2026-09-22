<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Campaign;
use App\Events\OrderUpdated;
use App\Models\OrderItem;
use App\Services\Audit\AuditService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderAction
{
    /**
     * Update order details and recalculate item line totals and order final amounts.
     *
     * @param Order $order Order instance to update.
     * @param array<string, mixed> $data Validated update payload.
     * @return Order Updated order with loaded relations.
     * @throws ValidationException If order is not in active state.
     */
    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->status->isActive() || $order->status === OrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'order' => __('admin.order_cannot_edit_cancelled'),
                ]);
            }

            $before = $order->only(['payment_method', 'note', 'subtotal', 'final_amount']);
            $orderUpdate = collect($data)->only(['payment_method', 'note'])->all();

            // Handle Item Price Adjustment if provided
            if (! empty($data['items']) && is_array($data['items'])) {
                $priceChanges = [];
                foreach ($data['items'] as $itemData) {
                    /** @var OrderItem|null $orderItem */
                    $orderItem = $order->items()->whereKey($itemData['id'] ?? 0)->first();
                    if ($orderItem && isset($itemData['unit_price'])) {
                        $oldPrice = $orderItem->unit_price;
                        $newPrice = (int) $itemData['unit_price'];

                        // Calculate toppings sum
                        $toppingsSum = (int) $orderItem->toppings()->sum('subtotal');
                        $campaign = Campaign::query()->lockForUpdate()->findOrFail($order->campaign_id);
                        $unitWithToppings = $newPrice + intdiv($toppingsSum, max(1, (int) $orderItem->quantity));
                        if ((int) $campaign->max_budget > 0 && $unitWithToppings > (int) $campaign->max_budget) {
                            throw ValidationException::withMessages([
                                'items' => __('admin.item_budget_limit_exceeded', [
                                    'limit' => FormatHelper::formatCurrency((int) $campaign->max_budget),
                                ]),
                            ]);
                        }
                        $orderItem->unit_price = $newPrice;
                        $orderItem->line_subtotal = ($newPrice * $orderItem->quantity) + $toppingsSum;
                        $orderItem->save();

                        $priceChanges[] = [
                            'item_id' => $orderItem->id,
                            'name' => $orderItem->item_name,
                            'old_price' => $oldPrice,
                            'new_price' => $newPrice,
                        ];
                    }
                }

                // Recalculate Subtotal and Final Amount
                $newSubtotal = (int) $order->items()->sum('line_subtotal');
                $selfPaidSubtotal = (int) $order->items()->where('is_self_paid', true)->sum('line_subtotal');
                $campaign ??= Campaign::query()->lockForUpdate()->findOrFail($order->campaign_id);
                $orderUpdate['subtotal'] = $newSubtotal;
                $orderUpdate['sponsor_amount'] = $this->recalculateSponsor($campaign, $order, $newSubtotal, $selfPaidSubtotal);
                $orderUpdate['final_amount'] = max(0, $newSubtotal + $order->delivery_amount - $order->discount_amount - $orderUpdate['sponsor_amount']);

                if (! empty($priceChanges)) {
                    $reason = (string) ($data['reason'] ?? __('admin.adjust_price'));
                    app(AuditService::class)->record('order.price_adjusted', 'order', $order->id, $order->room_id, [
                        'changes' => $priceChanges,
                        'reason' => $reason,
                    ], [
                        'new_subtotal' => $orderUpdate['subtotal'],
                        'new_final_amount' => $orderUpdate['final_amount'],
                    ]);

                    if ($order->roomUser) {
                        $formattedFinal = FormatHelper::formatCurrency((int) $orderUpdate['final_amount']);
                        app(\App\Services\Notification\UserNotificationService::class)->toRoomUser(
                            $order->roomUser,
                            'order.price_adjusted',
                            __('messages.order_price_adjusted_title'),
                            __('messages.order_price_adjusted_body', [
                                'order_id' => $order->id,
                                'amount' => $formattedFinal,
                                'reason' => $reason,
                            ]),
                            [
                                'order_id' => $order->id,
                                'reason' => $reason,
                                'final_amount' => $orderUpdate['final_amount'],
                                'room_id' => $order->room_id,
                            ]
                        );
                    }
                }
            }

            $order->update($orderUpdate);
            $updated = $order->fresh(['roomUser.globalUser', 'items.toppings', 'campaign']);
            app(AuditService::class)->record('order.updated', 'order', $order->id, $order->room_id, $before, $updated->only(['payment_method', 'note', 'subtotal', 'final_amount']));

            OrderUpdated::dispatch($updated, $order->status->value);

            return $updated;
        });
    }

    /**
     * Recalculate sponsorship after an administrator changes an order's prices.
     *
     * Items marked "trả riêng" (self-paid) are excluded from the sponsorable charge,
     * matching the exclusion already applied when the order was first created.
     *
     * @param Campaign $campaign Locked campaign policy.
     * @param Order $order Locked order.
     * @param int $subtotal Recalculated item subtotal (includes self-paid items).
     * @param int $selfPaidSubtotal Portion of $subtotal coming from self-paid items.
     * @return int Updated sponsorship amount.
     */
    private function recalculateSponsor(Campaign $campaign, Order $order, int $subtotal, int $selfPaidSubtotal = 0): int
    {
        $sponsorableSubtotal = max(0, $subtotal - $selfPaidSubtotal);
        $charge = max(0, $sponsorableSubtotal + (int) $order->delivery_amount - (int) $order->discount_amount);

        return match ($campaign->sponsor_type) {
            Campaign::SPONSOR_TYPE_FULL => $charge,
            default => 0,
        };
    }
}
