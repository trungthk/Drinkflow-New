<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\Room;
use App\Services\Notification\AdminNotificationService;
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
}
