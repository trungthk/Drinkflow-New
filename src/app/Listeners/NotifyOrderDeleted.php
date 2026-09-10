<?php

namespace App\Listeners;

use App\Events\OrderDeleted;
use App\Models\RoomUser;
use App\Services\Notification\UserNotificationService;

class NotifyOrderDeleted
{
    public function handle(OrderDeleted $event): void
    {
        $roomUser = RoomUser::query()->find($event->order['room_user_id']);
        if (! $roomUser) {
            return;
        }
        app(UserNotificationService::class)->toRoomUser($roomUser, 'order.deleted', 'Đơn hàng đã được xóa', 'Bạn có thể đặt lại đơn hàng.', ['order_id' => $event->order['order_id'], 'room_id' => $event->order['room_id']]);
    }
}
