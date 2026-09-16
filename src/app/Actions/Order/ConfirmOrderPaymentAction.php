<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\DebtStatus;
use App\Enums\PaymentStatus;
use App\Events\RoomRealtimeEvent;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\Debt;
use App\Models\Order;
use App\Models\RoomUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmOrderPaymentAction
{
    /**
     * Mark an order as pending admin confirmation and notify room admins.
     *
     * @param Order $order Order to confirm payment.
     * @param RoomUser $roomUser Room user who made the order.
     * @return Order Updated order entity.
     * @throws ValidationException If payment is already completed or unauthorized.
     */
    public function execute(Order $order, RoomUser $roomUser): Order
    {
        if ($order->room_id !== $roomUser->room_id || $order->room_user_id !== $roomUser->id) {
            throw ValidationException::withMessages([
                'order' => __('room.orders.unauthorized_order_access'),
            ]);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => __('room.orders.already_paid'),
            ]);
        }

        $updatedOrder = DB::transaction(function () use ($order, $roomUser): Order {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedOrder->payment_status = PaymentStatus::Pending;
            $lockedOrder->save();
            $paymentContent = 'DF'.$lockedOrder->id.' '.$roomUser->room_user_code;
            $paymentRequestedAt = now();

            // Synchronize or create pending debt record for room ledger
            $debt = Debt::query()
                ->where('campaign_id', $lockedOrder->campaign_id)
                ->where('room_user_id', $roomUser->id)
                ->lockForUpdate()
                ->first();

            if ($debt) {
                if ($debt->status !== DebtStatus::Paid) {
                    $debt->status = DebtStatus::Pending;
                    $debt->payment_requested_at = $paymentRequestedAt;
                    $debt->note = $paymentContent;
                    $debt->save();
                }
            } else {
                Debt::create([
                    'room_id' => $lockedOrder->room_id,
                    'campaign_id' => $lockedOrder->campaign_id,
                    'room_user_id' => $roomUser->id,
                    'original_amount' => (int) $lockedOrder->final_amount,
                    'sponsor_amount' => (int) $lockedOrder->sponsor_amount,
                    'sponsor_type' => $lockedOrder->campaign?->sponsor_type ?? 'none',
                    'sponsor_description' => $lockedOrder->campaign?->sponsor_description,
                    'adjustment_amount' => 0,
                    'paid_amount' => 0,
                    'remaining_amount' => (int) $lockedOrder->final_amount,
                    'status' => DebtStatus::Pending,
                    'payment_requested_at' => $paymentRequestedAt,
                    'note' => $paymentContent,
                ]);
            }

            return $lockedOrder;
        });

        $room = $updatedOrder->room;
        $userName = $roomUser->globalUser?->name ?? $roomUser->display_name ?? 'User #' . $roomUser->id;
        $amountFmt = number_format((int) $updatedOrder->final_amount, 0, ',', '.') . ' ₫';

        // Notify room admins via AdminNotification table
        $room->admins()->each(function (AdminAccount $admin) use ($room, $updatedOrder, $userName, $amountFmt): void {
            AdminNotification::create([
                'admin_id' => $admin->id,
                'room_id' => $room->id,
                'type' => 'order.payment_submitted',
                'title' => __('admin.payment_confirmation_request_title'),
                'body' => __('admin.payment_confirmation_request_body', [
                    'user' => $userName,
                    'amount' => $amountFmt,
                    'order' => 'DF' . $updatedOrder->id,
                ]),
                'data' => [
                    'order_id' => $updatedOrder->id,
                    'campaign_id' => $updatedOrder->campaign_id,
                    'amount' => (int) $updatedOrder->final_amount,
                    'user_name' => $userName,
                ],
            ]);
        });

        // Broadcast realtime socket event to room channel
        RoomRealtimeEvent::dispatch('order.payment_submitted', $room->id, [
            'order_id' => $updatedOrder->id,
            'campaign_id' => $updatedOrder->campaign_id,
            'room_id' => $room->id,
            'room_user_id' => $roomUser->id,
            'user_name' => $userName,
            'user_code' => $roomUser->room_user_code,
            'amount' => (int) $updatedOrder->final_amount,
            'status' => 'pending',
            'message' => __('room.orders.payment_pending_socket_message', [
                'user' => $userName,
                'amount' => $amountFmt,
            ]),
        ]);

        return $updatedOrder;
    }
}
