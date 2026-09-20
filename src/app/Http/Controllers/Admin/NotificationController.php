<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\GlobalUserStatus;
use App\Enums\RoomUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BroadcastNotificationRequest;
use App\Models\AdminAccount;
use App\Models\Room;
use App\Services\Notification\AdminNotificationService;
use App\Services\Notification\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark all unread room audit notifications as read for the authenticated admin.
     *
     * @param Request $request Incoming request.
     * @param Room $room Current room.
     * @param AdminNotificationService $notifications Personal notification service.
     * @return JsonResponse Number of notifications marked as read.
     */
    public function markAllRead(Request $request, Room $room, AdminNotificationService $notifications): JsonResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');

        return response()->json([
            'marked_count' => $notifications->markAllReadForRoom($admin, $room),
        ]);
    }

    /**
     * Broadcast a web and realtime notification to active members of the room.
     *
     * @param BroadcastNotificationRequest $request Incoming validated request.
     * @param Room $room Current room.
     * @param UserNotificationService $notifications User notification delivery service.
     * @return JsonResponse Delivery result.
     */
    public function broadcast(BroadcastNotificationRequest $request, Room $room, UserNotificationService $notifications): JsonResponse
    {
        $validated = $request->validated();

        $count = $room->roomUsers()
            ->where('status', RoomUserStatus::Active->value)
            ->whereHas('globalUser', static fn ($query) => $query->where('status', GlobalUserStatus::Active->value))
            ->count();
        $notifications->toRoom(
            $room,
            $validated['type'],
            $validated['title'],
            $validated['body'] ?? null,
            ['room_id' => $room->id, 'broadcast' => true]
        );

        return response()->json(['message' => __('admin.broadcast_sent', ['count' => $count]), 'count' => $count]);
    }
}
