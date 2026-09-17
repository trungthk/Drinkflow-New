<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Enums\OrderStatus;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Facades\DB;

class UserRoomDashboardService
{
    /**
     * Aggregate all data required for the Room User Dashboard view.
     *
     * @param Room $room Current active room instance.
     * @param RoomUser $roomUser Current user's membership in this room.
     * @param GlobalUser|null $user Global authenticated user instance.
     * @return array<string, mixed> Keyed array of view parameters for the dashboard.
     */
    public function getDashboardData(Room $room, RoomUser $roomUser, ?GlobalUser $user): array
    {
        $activeCampaign = $room->campaigns()
            ->where('status', CampaignStatus::Active->value)
            ->first();

        $campaignData = null;
        if ($activeCampaign) {
            $sponsorUsed = (int) Order::where('campaign_id', $activeCampaign->id)->sum('sponsor_amount');
            $sponsorBudget = 200000;
            $sponsorRemaining = max(0, $sponsorBudget - $sponsorUsed);
            $sponsorPercent = $sponsorBudget > 0 ? min(100.0, round(($sponsorUsed / $sponsorBudget) * 100, 1)) : 0.0;
            $popularItems = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.campaign_id', $activeCampaign->id)
                ->where('orders.status', '!=', OrderStatus::Cancelled->value)
                ->selectRaw('order_items.item_name, SUM(order_items.quantity) AS total_quantity')
                ->groupBy('order_items.item_name')
                ->orderByDesc('total_quantity')
                ->orderBy('order_items.item_name')
                ->limit(5)
                ->get()
                ->map(static fn (object $item): object => (object) [
                    'name' => (string) $item->item_name,
                    'quantity' => (int) $item->total_quantity,
                ]);

            $campaignData = [
                'id' => $activeCampaign->id,
                'name' => $activeCampaign->name,
                'restaurant' => $activeCampaign->restaurant ?? __('room.dashboard.partner'),
                'creator_name' => 'Admin',
                'description' => $activeCampaign->description ?? '',
                'deadline' => $activeCampaign->deadline,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
                'deadline_formatted' => $activeCampaign->deadline ? FormatHelper::formatDateTime($activeCampaign->deadline, 'H:i') : '10:30',
                'sponsor_budget' => $sponsorBudget,
                'sponsor_remaining' => $sponsorRemaining,
                'sponsor_used' => $sponsorUsed,
                'sponsor_percent' => $sponsorPercent,
                'popular_items' => $popularItems,
                'order_url' => route('user.campaigns.order-page', [$room->slug, $activeCampaign->id]),
            ];
        }

        // Unpaid debts in this room
        $unpaidDebts = Debt::where('room_user_id', $roomUser->id)
            ->where('room_id', $room->id)
            ->where('status', DebtStatus::Unpaid->value)
            ->sum('remaining_amount');

        // Recent orders by this user in this room
        $userRecentOrders = $roomUser->orders()->where('status', '!=', OrderStatus::Cancelled->value)->with('items')->latest()->take(5)->get();

        // All active rooms of user for dropdown
        $userRooms = $user ? $user->rooms()->where('rooms.status', RoomStatus::Active->value)->get() : collect();

        // Unread notifications count
        $unreadCount = $user ? DB::table('user_notifications')
            ->where('global_user_id', $user->id)
            ->whereNull('read_at')
            ->count() : 0;

        return [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'activeCampaign' => $campaignData,
            'unpaidDebts' => (int) $unpaidDebts,
            'userRecentOrders' => $userRecentOrders,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ];
    }
}
