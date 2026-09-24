<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Services\Notification\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read receipts for the signed-in superadmin's own notification inbox (header bell + notifications page).
 */
class AdminNotificationController extends Controller
{
    /**
     * Mark one notification of the signed-in account as read.
     *
     * @param Request $request Incoming request.
     * @param AdminNotification $notification Notification to mark; 404 when it belongs to another account.
     * @param AdminNotificationService $notifications Admin notification service.
     * @return JsonResponse Remaining unread count for the header badge.
     */
    public function read(Request $request, AdminNotification $notification, AdminNotificationService $notifications): JsonResponse
    {
        $admin = $this->admin($request);
        abort_unless($notifications->markRead($admin, $notification), 404);

        return response()->json(['data' => ['unread_count' => $notifications->unreadCountForAdmin($admin)]]);
    }

    /**
     * Mark every notification of the signed-in account as read.
     *
     * @param Request $request Incoming request.
     * @param AdminNotificationService $notifications Admin notification service.
     * @return JsonResponse Number of notifications marked as read.
     */
    public function markAllRead(Request $request, AdminNotificationService $notifications): JsonResponse
    {
        return response()->json(['data' => ['marked_count' => $notifications->markAllReadForAdmin($this->admin($request))]]);
    }

    /**
     * Resolve the authenticated admin account (the superadmin middleware already guarantees one).
     *
     * @param Request $request Incoming request.
     * @return AdminAccount Signed-in account.
     */
    private function admin(Request $request): AdminAccount
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');

        return $admin;
    }
}
