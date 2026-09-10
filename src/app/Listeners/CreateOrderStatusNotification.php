<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Models\UserNotification;

class CreateOrderStatusNotification
{
    public function handle(OrderUpdated $event): void
    {
        $order = $event->order;
        UserNotification::create(['global_user_id' => $order->roomUser->global_user_id, 'room_user_id' => $order->room_user_id, 'type' => 'order.updated', 'title' => __('messages.order_status_updated'), 'body' => 'Đơn hàng #'.$order->id.' chuyển sang '.$order->status->value.'.', 'data' => ['order_id' => $order->id, 'status' => $order->status->value, 'room_id' => $order->room_id]]);
    }
}
