<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\CampaignStatus;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRoomProfileService
{
    /**
     * Tổng hợp thông tin cá nhân và thống kê số lượng đơn hàng, chi tiêu, top món yêu thích của người dùng trong phòng.
     *
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Models\RoomUser  $roomUser  Thành viên phòng của người dùng hiện tại
     * @param  \App\Models\GlobalUser|null  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @return array<string, mixed>  Mảng dữ liệu phân loại theo JSON hoặc View Profile phòng
     */
    public function getRoomProfileData(Room $room, RoomUser $roomUser, ?GlobalUser $user, Request $request): array
    {
        $orders = $roomUser->orders()->with('items')->get();
        $totalOrders = $orders->count();
        $totalSpent = (int) $orders->sum('final_amount');
        $totalSponsor = (int) $orders->sum('sponsor_amount');
        $items = $orders->pluck('items')->flatten();
        $favoriteItems = $items->groupBy('item_name')
            ->map(fn ($rows, $name) => ['name' => $name, 'count' => (int) $rows->sum('quantity')])
            ->sortByDesc('count')
            ->take(5);

        $unreadCount = $user ? DB::table('user_notifications')
            ->where('global_user_id', $user->id)
            ->whereNull('read_at')
            ->count() : 0;

        if ($request->expectsJson()) {
            return [
                'is_json' => true,
                'data' => [
                    'room_user' => $roomUser,
                    'stats' => [
                        'total_orders' => $totalOrders,
                        'total_spent' => $totalSpent,
                        'total_sponsor' => $totalSponsor,
                    ],
                    'favorite_items' => $favoriteItems,
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
                'totalOrders' => $totalOrders,
                'totalSpent' => $totalSpent,
                'totalSponsor' => $totalSponsor,
                'favoriteItems' => $favoriteItems,
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
