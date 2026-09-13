<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Models\UserNotification;
use App\Services\Notification\UserNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Hiển thị trang danh sách thông báo toàn hệ thống hoặc trả về JSON API (/me/notifications).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Notification\UserNotificationService  $service  Service xử lý thông báo
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View  Phản hồi JSON hoặc Giao diện View
     */
    public function index(Request $request, UserNotificationService $service): JsonResponse|View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        // Return JSON if requested as API
        if ($request->expectsJson() || $request->routeIs('user.notifications.index')) {
            $data = $service->getNotificationsApiData($user, $request);
            return response()->json(['data' => $data]);
        }

        $data = $service->getNotificationsPageData($user, $request);

        return view('user.global.notifications', $data);
    }

    /**
     * Đánh dấu toàn bộ thông báo chưa đọc của người dùng thành đã đọc.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Notification\UserNotificationService  $service  Service xử lý thông báo
     * @return \Illuminate\Http\RedirectResponse  Phản hồi chuyển hướng quay lại kèm thông báo thành công
     */
    public function markAllRead(Request $request, UserNotificationService $service): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $service->markAllAsRead($user);

        return back()->with('status', __('global.notifications.marked_all_read_status'));
    }

    /**
     * Đánh dấu một thông báo cụ thể thành đã đọc (hỗ trợ cả Web Redirect và JSON API).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\UserNotification  $notification  Bản ghi thông báo cần đánh dấu
     * @param  \App\Services\Notification\UserNotificationService  $service  Service xử lý thông báo
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse  Phản hồi JSON hoặc chuyển hướng
     */
    public function read(Request $request, UserNotification $notification, UserNotificationService $service): JsonResponse|RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $updated = $service->markAsRead($user, $notification);

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', __('global.notifications.marked_read_status'));
    }
}

