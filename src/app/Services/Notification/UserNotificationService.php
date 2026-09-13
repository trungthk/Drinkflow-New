<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\RoomUserStatus;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class UserNotificationService
{
    /**
     * Gửi thông báo đến toàn bộ thành viên đang hoạt động trong phòng.
     *
     * @param  \App\Models\Room  $room  Đối tượng phòng nhận thông báo
     * @param  string  $type  Loại thông báo (ví dụ: campaign.created, room.notice)
     * @param  string  $title  Tiêu đề thông báo
     * @param  string|null  $body  Nội dung chi tiết thông báo
     * @param  array<string, mixed>  $data  Dữ liệu bổ sung (metadata/payload)
     * @return void
     */
    public function toRoom(Room $room, string $type, string $title, ?string $body = null, array $data = []): void
    {
        $room->roomUsers()->where('status', RoomUserStatus::Active->value)->each(function (RoomUser $roomUser) use ($type, $title, $body, $data): void {
            $this->toRoomUser($roomUser, $type, $title, $body, $data);
        });
    }

    /**
     * Gửi thông báo đến một thành viên phòng cụ thể.
     *
     * @param  \App\Models\RoomUser  $roomUser  Thành viên phòng nhận thông báo
     * @param  string  $type  Loại thông báo
     * @param  string  $title  Tiêu đề thông báo
     * @param  string|null  $body  Nội dung chi tiết
     * @param  array<string, mixed>  $data  Dữ liệu bổ sung
     * @return \App\Models\UserNotification  Bản ghi thông báo vừa tạo
     */
    public function toRoomUser(RoomUser $roomUser, string $type, string $title, ?string $body = null, array $data = []): UserNotification
    {
        return UserNotification::create([
            'global_user_id' => $roomUser->global_user_id,
            'room_user_id' => $roomUser->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
    /**
     * Lấy danh sách thông báo phân trang theo từng tab và tính số lượng thông báo theo danh mục.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa query tab
     * @return array<string, mixed>  Mảng dữ liệu cho View thông báo
     */
    public function getNotificationsPageData(GlobalUser $user, Request $request): array
    {
        $tab = $request->query('tab', 'all');
        $baseQuery = $user->notifications();

        // Calculate counts for filter tabs
        $allCount = (clone $baseQuery)->count();
        $unreadCount = (clone $baseQuery)->whereNull('read_at')->count();
        $roomOrderCount = (clone $baseQuery)->whereIn('type', ['campaign.created', 'order.status', 'room.invite'])->count();
        $paymentCount = (clone $baseQuery)->whereIn('type', ['payment.due', 'payment.confirmed', 'debt.reminder'])->count();
        $securityCount = (clone $baseQuery)->whereIn('type', ['security.alert', 'device.new'])->count();

        // Query by active tab
        $query = (clone $baseQuery)->latest();
        if ($tab === 'unread') {
            $query->whereNull('read_at');
        } elseif ($tab === 'room_order') {
            $query->whereIn('type', ['campaign.created', 'order.status', 'room.invite']);
        } elseif ($tab === 'payment') {
            $query->whereIn('type', ['payment.due', 'payment.confirmed', 'debt.reminder']);
        } elseif ($tab === 'security') {
            $query->whereIn('type', ['security.alert', 'device.new']);
        }

        $notifications = $query->paginate(15)->appends(['tab' => $tab]);

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.notifications.breadcrumb_notifications'), 'url' => route('user.me.notifications')],
        ];

        return compact(
            'user',
            'tab',
            'allCount',
            'unreadCount',
            'roomOrderCount',
            'paymentCount',
            'securityCount',
            'notifications',
            'breadcrumbs'
        );
    }

    /**
     * Lấy danh sách thông báo phân trang dạng JSON API.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa tùy chọn lọc unread
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator  Phân trang thông báo
     */
    public function getNotificationsApiData(GlobalUser $user, Request $request): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();
        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return $query->paginate(30);
    }

    /**
     * Đánh dấu toàn bộ thông báo chưa đọc của người dùng thành đã đọc.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @return void
     */
    public function markAllAsRead(GlobalUser $user): void
    {
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * Đánh dấu một thông báo cụ thể thành đã đọc.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng sở hữu thông báo
     * @param  \App\Models\UserNotification  $notification  Bản ghi thông báo cần đánh dấu
     * @return \App\Models\UserNotification  Bản ghi thông báo sau khi cập nhật
     */
    public function markAsRead(GlobalUser $user, UserNotification $notification): UserNotification
    {
        abort_unless($notification->global_user_id === $user->id, 404);
        $notification->update(['read_at' => now()]);

        return $notification->fresh();
    }
}
