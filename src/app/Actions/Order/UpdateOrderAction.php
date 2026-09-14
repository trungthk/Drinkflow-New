<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Events\OrderUpdated;
use App\Services\Audit\AuditService;
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
            if (! $order->status->isActive()) {
                throw ValidationException::withMessages([
                    'order' => __('admin.order_must_be_active_to_edit'),
                ]);
            }

            $before = $order->only(['payment_method', 'note', 'subtotal', 'final_amount']);
            $orderUpdate = collect($data)->only(['payment_method', 'note'])->all();

            // Handle Item Price Adjustment if provided
            if (! empty($data['items']) && is_array($data['items'])) {
                $priceChanges = [];
                foreach ($data['items'] as $itemData) {
                    $orderItem = $order->items()->whereKey($itemData['id'] ?? 0)->first();
                    if ($orderItem && isset($itemData['unit_price'])) {
                        $oldPrice = $orderItem->unit_price;
                        $newPrice = (int) $itemData['unit_price'];

                        // Calculate toppings sum
                        $toppingsSum = (int) $orderItem->toppings()->sum('subtotal');
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
                $orderUpdate['subtotal'] = $newSubtotal;
                $orderUpdate['final_amount'] = max(0, $newSubtotal + $order->delivery_amount - $order->discount_amount - $order->sponsor_amount);

                if (! empty($priceChanges)) {
                    app(AuditService::class)->record('order.price_adjusted', 'order', $order->id, $order->room_id, [
                        'changes' => $priceChanges,
                        'reason' => $data['reason'] ?? 'Price adjustment',
                    ], [
                        'new_subtotal' => $orderUpdate['subtotal'],
                        'new_final_amount' => $orderUpdate['final_amount'],
                    ]);
                }
            }

            $order->update($orderUpdate);
            $updated = $order->fresh(['roomUser.globalUser', 'items.toppings', 'campaign']);
            app(AuditService::class)->record('order.updated', 'order', $order->id, $order->room_id, $before, $updated->only(['payment_method', 'note', 'subtotal', 'final_amount']));

            OrderUpdated::dispatch($updated, $order->status->value);

            return $updated;
        });
    }
}
