<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\OrderCreated;
use App\Models\UserNotification;

class CreateOrderNotification
{
    /**
     * Notify the orderer that their order was recorded, linking to the order page.
     *
     * @param OrderCreated $event Order creation event.
     * @return void
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        UserNotification::create([
            'global_user_id' => $order->roomUser->global_user_id,
            'room_user_id' => $order->room_user_id,
            'type' => NotificationType::OrderCreated->value,
            'title' => __('messages.order_created'),
            'body' => __('messages.order_created_body', ['order_code' => $order->code]),
            'link' => $order->room ? route('user.orders.page', [$order->room, $order]) : null,
            'data' => ['order_id' => $order->id, 'order_code' => $order->code, 'room_id' => $order->room_id],
        ]);
    }
}
