<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\CampaignStatus;
use App\Enums\AnalyticsPeriod;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;

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
     * @throws \Illuminate\Validation\ValidationException When the requested period is invalid.
     */
    public function getRoomAnalyticsData(Room $room, RoomUser $roomUser, ?GlobalUser $user, Request $request): array
    {
        $validated = $request->validate(['period' => ['nullable', Rule::enum(AnalyticsPeriod::class)]]);
        $period = AnalyticsPeriod::from($validated['period'] ?? AnalyticsPeriod::Week->value);
        $now = CarbonImmutable::now();
        $bounds = $period->bounds($now);
        $orders = $roomUser->orders()
            ->where('room_id', $room->id)
            ->whereHas('campaign', function ($campaignQuery) use ($bounds): void {
                $campaignQuery->where('status', CampaignStatus::Closed->value)
                    ->where('closed_at', '>=', $bounds['start'])
                    ->where('closed_at', '<', $bounds['end']);
            })
            ->where('status', '!=', OrderStatus::Cancelled)
            ->with('items')->get();
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

        $totalRoomCampaigns = Campaign::where('room_id', $room->id)
            ->where('status', CampaignStatus::Closed->value)
            ->where('closed_at', '>=', $bounds['start'])
            ->where('closed_at', '<', $bounds['end'])->count();
        $participatedCampaigns = $orders->pluck('campaign_id')->filter()->unique()->count();
        $participationRate = $totalRoomCampaigns > 0
            ? (int) min(100, round(($participatedCampaigns / $totalRoomCampaigns) * 100)) : 0;
        $elapsedWeeks = max(1, $bounds['start']->diffInDays($now->addDay()->startOfDay()) / CarbonImmutable::DAYS_PER_WEEK);
        $weeklyAverage = (int) round($totalAmount / $elapsedWeeks);
        $savingsPercent = $totalAmount + $sponsorReceived > 0
            ? (int) round($sponsorReceived / ($totalAmount + $sponsorReceived) * 100) : 0;

        if ($request->expectsJson()) {
            return [
                'is_json' => true,
                'data' => [
                    'total_orders' => $totalOrders,
                    'total_cups' => $totalCups,
                    'total_amount' => $totalAmount,
                    'sponsor_received' => $sponsorReceived,
                    'top_items' => $topItems,
                    'period' => $period->value,
                    'period_start' => $bounds['start']->toDateString(),
                    'period_end' => $bounds['end']->subDay()->toDateString(),
                    'participation_rate' => $participationRate,
                    'weekly_average' => $weeklyAverage,
                    'savings_percent' => $savingsPercent,
                ],
            ];
        }

        $activeCampaign = $room->campaigns()->where('status', CampaignStatus::Active->value)->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', RoomStatus::Active)->get() : collect();
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
                'savingsPercent' => $savingsPercent,
                'period' => $period,
                'periodStart' => $bounds['start'],
                'periodEnd' => $bounds['end']->subDay(),
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
