<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\CampaignStatus;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRoomNotificationService
{
    /**
     * Thu thập danh sách thông báo liên quan đến người dùng trong phạm vi một phòng cụ thể.
     *
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Models\RoomUser|null  $roomUser  Thành viên phòng của người dùng hiện tại
     * @param  \App\Models\GlobalUser|null  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @return array<string, mixed>  Mảng dữ liệu phân biệt giữa JSON API hoặc View thông báo phòng
     */
    public function getRoomNotificationData(Room $room, ?RoomUser $roomUser, ?GlobalUser $user, Request $request): array
    {
        $notifications = DB::table('user_notifications')
            ->where('global_user_id', $user?->id)
            ->where(function ($q) use ($roomUser) {
                $q->where('room_user_id', $roomUser?->id)
                  ->orWhereNull('room_user_id');
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->type,
                    'title' => $notif->title,
                    'body' => $notif->body,
                    'is_read' => !is_null($notif->read_at),
                    'created_at_formatted' => Carbon::parse($notif->created_at)->diffForHumans(),
                    'created_at_time' => Carbon::parse($notif->created_at)->format('H:i, d/m/Y'),
                    'category' => str_contains($notif->type, 'order') ? 'orders' : (str_contains($notif->type, 'campaign') ? 'campaigns' : (str_contains($notif->type, 'debt') ? 'debts' : 'general')),
                ];
            });

        $unreadCount = $notifications->where('is_read', false)->count();

        if ($request->expectsJson()) {
            return [
                'is_json' => true,
                'data' => [
                    'data' => $notifications,
                    'unread_count' => $unreadCount,
                ],
            ];
        }

        $activeCampaign = $room->campaigns()->where('status', CampaignStatus::Active->value)->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();

        return [
            'is_json' => false,
            'view_data' => [
                'room' => $room,
                'roomUser' => $roomUser,
                'user' => $user,
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'unreadNotificationsCount' => $unreadCount,
                'activeCampaign' => $activeCampaign ? [
                    'name' => $activeCampaign->name,
                    'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
                ] : null,
                'userRooms' => $userRooms,
            ],
        ];
    }
}
