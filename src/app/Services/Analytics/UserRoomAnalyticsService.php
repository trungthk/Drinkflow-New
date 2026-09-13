<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRoomAnalyticsService
{
    /**
     * Tính toán số liệu thống kê chi tiêu, tỷ lệ tham gia và top món yêu thích của người dùng trong phạm vi một phòng cụ thể.
     *
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Models\RoomUser  $roomUser  Thành viên phòng của người dùng hiện tại
     * @param  \App\Models\GlobalUser|null  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @return array<string, mixed>  Mảng dữ liệu phân biệt giữa JSON API hoặc View thống kê phòng
     */
    public function getRoomAnalyticsData(Room $room, RoomUser $roomUser, ?GlobalUser $user, Request $request): array
    {
        $orders = $roomUser->orders()->where('room_id', $room->id)->with('items')->get();
        $items = $orders->pluck('items')->flatten();

        $totalOrders = $orders->count();
        $totalCups = (int) $items->sum('quantity');
        $totalAmount = (int) $orders->sum('final_amount');
        $sponsorReceived = (int) $orders->sum('sponsor_amount');

        $topItems = $items->groupBy('item_name')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'quantity' => (int) $rows->sum('quantity'),
                'total_amount' => (int) $rows->sum('line_subtotal'),
            ])
            ->sortByDesc('quantity')
            ->values()
            ->take(10);

        if ($request->expectsJson()) {
            return [
                'is_json' => true,
                'data' => [
                    'total_orders' => $totalOrders,
                    'total_cups' => $totalCups,
                    'total_amount' => $totalAmount,
                    'sponsor_received' => $sponsorReceived,
                    'top_items' => $topItems,
                ],
            ];
        }

        $totalRoomCampaigns = Campaign::where('room_id', $room->id)->count();
        $participatedCampaigns = $orders->pluck('campaign_id')->unique()->count();
        $participationRate = $totalRoomCampaigns > 0 ? min(100, round(($participatedCampaigns / $totalRoomCampaigns) * 100)) : 88;
        $weeklyAverage = $totalOrders > 0 ? round($totalAmount / max(1, ceil($orders->min('created_at') ? now()->diffInWeeks($orders->min('created_at')) : 4))) : 0;

        $activeCampaign = $room->campaigns()->where('status', CampaignStatus::Active->value)->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();
        $unreadCount = $user ? DB::table('user_notifications')->where('global_user_id', $user->id)->whereNull('read_at')->count() : 0;

        return [
            'is_json' => false,
            'view_data' => [
                'room' => $room,
                'roomUser' => $roomUser,
                'user' => $user,
                'totalOrders' => $totalOrders,
                'totalCups' => $totalCups,
                'totalAmount' => $totalAmount,
                'sponsorReceived' => $sponsorReceived,
                'topItems' => $topItems,
                'participationRate' => $participationRate,
                'weeklyAverage' => $weeklyAverage,
                'activeCampaign' => $activeCampaign ? [
                    'name' => $activeCampaign->name,
                    'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
                ] : null,
                'userRooms' => $userRooms,
                'unreadNotificationsCount' => $unreadCount,
            ],
        ];
    }
}
