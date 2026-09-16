<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Services\Notification\UserNotificationService;

class CreateOrderStatusNotification
{
    /**
     * Create a new listener instance.
     *
     * @param UserNotificationService $notificationService Service to handle user notification delivery.
     */
    public function __construct(
        private readonly UserNotificationService $notificationService
    ) {}

    /**
     * Handle the order updated event.
     *
     * @param OrderUpdated $event The dispatched order updated event.
     * @return void
     */
    public function handle(OrderUpdated $event): void
    {
        $order = $event->order;
        $roomUser = $order->roomUser;

        if (! $roomUser) {
            return;
        }

        // Only send status change notification when status actually changed
        if ($order->status->value === $event->previousStatus) {
            return;
        }

        // Prevent duplicate notification within 5 seconds for the same order status
        $alreadyCreated = \App\Models\UserNotification::where('room_user_id', $roomUser->id)
            ->where('type', 'order.status')
            ->where('created_at', '>=', now()->subSeconds(5))
            ->whereRaw("data->>'order_id' = ?", [(string) $order->id])
            ->whereRaw("data->>'status' = ?", [$order->status->value])
            ->exists();

        if ($alreadyCreated) {
            return;
        }

        $statusKey = 'admin.status_'.$order->status->value;
        $statusLabel = __($statusKey);
        if ($statusLabel === $statusKey) {
            $statusLabel = $order->status->value;
        }

        $this->notificationService->toRoomUser(
            $roomUser,
            'order.status',
            __('messages.order_status_updated'),
            __('messages.order_status_updated_body', [
                'order_id' => $order->id,
                'status' => $statusLabel,
            ]),
            [
                'order_id' => $order->id,
                'status' => $order->status->value,
                'room_id' => $order->room_id,
            ]
        );
    }
}

