<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Superadmin;
use App\Models\SuperadminNotification;
use App\Services\Notification\SuperadminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read receipts for the signed-in superadmin's own platform notification inbox (header bell + notifications page).
 *
 * Every action is bound to the signed-in account, so a Superadmin can never read or clear another
 * account's notifications. The route names keep the historical `admin-notifications` prefix.
 */
class AdminNotificationController extends Controller
{
    /**
     * Mark one notification of the signed-in account as read.
     *
     * @param Request $request Incoming request.
     * @param SuperadminNotification $notification Notification to mark; 404 when it belongs to another account.
     * @param SuperadminNotificationService $notifications Notification service.
     * @return JsonResponse Remaining unread count for the header badge.
     */
    public function read(Request $request, SuperadminNotification $notification, SuperadminNotificationService $notifications): JsonResponse
    {
        $admin = $this->admin($request);
        abort_unless($notifications->markRead($admin, $notification), 404);

        return response()->json(['data' => ['unread_count' => $notifications->unreadCountFor($admin)]]);
    }

    /**
     * Mark every notification of the signed-in account as read.
     *
     * @param Request $request Incoming request.
     * @param SuperadminNotificationService $notifications Notification service.
     * @return JsonResponse Number of notifications marked as read.
     */
    public function markAllRead(Request $request, SuperadminNotificationService $notifications): JsonResponse
    {
        return response()->json(['data' => ['marked_count' => $notifications->markAllRead($this->admin($request))]]);
    }

    /**
     * Resolve the authenticated admin account (the superadmin middleware already guarantees one).
     *
     * @param Request $request Incoming request.
     * @return Superadmin Signed-in account.
     */
    private function admin(Request $request): Superadmin
    {
        /** @var Superadmin $admin */
        $admin = $request->user('superadmin');

        return $admin;
    }
}
