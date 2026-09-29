<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\CampaignStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Events\OrderUpdated;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Audit\AuditService;
use App\Services\Notification\UserNotificationService;
use App\Services\Order\OrderPricingService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderItemsAction
{
    public function __construct(
        private readonly OrderPricingService $pricing,
        private readonly AuditService $audit,
        private readonly UserNotificationService $notifications,
    ) {
    }

    /**
     * Let a room admin replace the items of a member's order (admin "sửa đơn").
     *
     * The new lines are priced from the campaign menu with the same rules as a new order (per-line cap,
     * self-paid exclusion from sponsorship, debt ceiling), so earlier manual price adjustments are replaced.
     * Only the order's own member is billed: ordering for someone else (proxy lines) is not supported here.
     *
     * @param Order $order Order being edited; must still be active, unpaid and in a live campaign (even past its deadline).
     * @param Admin $admin Admin editing the order.
     * @param array<string, mixed> $data Validated payload (items[], note).
     * @return Order Updated order with its items and member loaded.
     * @throws ValidationException When the order or campaign can no longer be edited, or a line is invalid.
     */
    public function execute(Order $order, Admin $admin, array $data): Order
    {
        $updated = DB::transaction(function () use ($order, $admin, $data): Order {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($order->campaign_id);
            $order = Order::query()->with('items.toppings')->lockForUpdate()->findOrFail($order->id);
            $this->ensureEditable($order, $campaign);

            ['lines' => $lines, 'subtotal' => $subtotal] = $this->pricing->priceLines($campaign, $data['items'] ?? []);
            $delivery = (int) $order->delivery_amount;
            $discount = (int) $order->discount_amount;
            $sponsor = $this->pricing->sponsorAmount($campaign, $lines, $subtotal, $delivery, $discount, $order->id);
            $final = max(0, $subtotal + $delivery - $discount - $sponsor);
            $order->loadMissing('roomUser');
            $this->pricing->enforceDebtPolicy($order->roomUser, $final);

            $before = [
                'items' => $this->summarize($order),
                'note' => $order->note,
                'subtotal' => (int) $order->subtotal,
                'final_amount' => (int) $order->final_amount,
            ];

            $order->items()->delete();
            $this->pricing->createItems($order, $lines);
            $order->update([
                'subtotal' => $subtotal,
                'sponsor_amount' => $sponsor,
                'final_amount' => $final,
                'note' => $data['note'] ?? null,
            ]);

            $order->load(['roomUser.globalUser', 'items.toppings', 'campaign']);
            $this->audit->record('order.items_updated', 'order', $order->id, $order->room_id, $before, [
                'items' => $this->summarize($order),
                'note' => $order->note,
                'subtotal' => $subtotal,
                'final_amount' => $final,
            ], ['updated_by_admin_id' => $admin->id]);

            return $order;
        });

        if ($updated->roomUser !== null) {
            $this->notifications->toRoomUser(
                $updated->roomUser,
                NotificationType::OrderUpdated->value,
                __('messages.order_items_updated_title'),
                __('messages.order_items_updated_body', [
                    'order_code' => $updated->code,
                    'amount' => FormatHelper::formatCurrency((int) $updated->final_amount),
                ]),
                [
                    'order_id' => $updated->id,
                    'room_id' => $updated->room_id,
                    'final_amount' => (int) $updated->final_amount,
                ],
            );
        }

        OrderUpdated::dispatch($updated, $updated->status->value);

        return $updated;
    }

    /**
     * Guard that the order may still have its items changed.
     *
     * @param Order $order Locked order.
     * @param Campaign $campaign Locked campaign of the order.
     * @return void
     * @throws ValidationException When the order is inactive, (being) paid, or the campaign is no longer live.
     */
    private function ensureEditable(Order $order, Campaign $campaign): void
    {
        if (! $order->status->isActive()) {
            throw ValidationException::withMessages([
                'order' => __('admin.order_cannot_edit_cancelled'),
            ]);
        }
        if (in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::Pending], true)) {
            throw ValidationException::withMessages([
                'order' => __('admin.edit_order_paid'),
            ]);
        }
        // Admins may still fix orders after the ordering deadline, as long as the campaign has not been closed.
        if ($campaign->status !== CampaignStatus::Active) {
            throw ValidationException::withMessages([
                'campaign' => __('admin.campaign_locked_cannot_modify'),
            ]);
        }
    }

    /**
     * Summarize an order's lines for the audit log.
     *
     * @param Order $order Order with items.toppings loaded.
     * @return array<int, string> One "2× Name (Size) + Topping [self-paid]" entry per line.
     */
    private function summarize(Order $order): array
    {
        return $order->items->map(static fn (OrderItem $item): string => $item->quantity . '× ' . $item->item_name
            . ($item->size_name ? ' (' . $item->size_name . ')' : '')
            . $item->toppings->map(static fn ($topping): string => ' + ' . $topping->topping_name)->implode('')
            . ($item->is_self_paid ? ' [self-paid]' : ''))->values()->all();
    }
}
