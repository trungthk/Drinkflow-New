<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\UserNotification;

class CreateOrderNotification
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        UserNotification::create(['global_user_id' => $order->roomUser->global_user_id, 'room_user_id' => $order->room_user_id, 'type' => 'order.created', 'title' => __('messages.order_created'), 'body' => 'Đơn hàng #' . $order->id . ' đã được ghi nhận.', 'data' => ['order_id' => $order->id, 'room_id' => $order->room_id]]);
    }
}
