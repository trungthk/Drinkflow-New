<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\RoomStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Campaign\UserRoomCampaignService;
use App\Services\Reporting\SponsorLeaderboardService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserRoomDashboardService
{
    /**
     * @param UserRoomCampaignService $campaignService Nguồn dữ liệu (đã cache) cho Top món được chọn nhiều nhất.
     * @param SponsorLeaderboardService $sponsorLeaderboard Resolves actual campaign sponsors (not subsidy beneficiaries).
     */
    public function __construct(
        private readonly UserRoomCampaignService $campaignService,
        private readonly SponsorLeaderboardService $sponsorLeaderboard,
    ) {
    }

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
            // Campaigns have no total sponsorship budget: only a policy (sponsor_type) and an optional
            // per-product cap (max_budget), the same values the campaign menu page shows.
            $sponsorUsed = (int) Order::where('campaign_id', $activeCampaign->id)
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->sum('sponsor_amount');
            $popularItems = $this->campaignService->getFavoriteItems($activeCampaign, (int) $roomUser->global_user_id);

            $campaignData = [
                'id' => $activeCampaign->id,
                'name' => $activeCampaign->name,
                'restaurant' => $activeCampaign->restaurant ?? __('room.dashboard.partner'),
                'creator_name' => 'Admin',
                'description' => $activeCampaign->description ?? '',
                'deadline' => $activeCampaign->deadline,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
                'deadline_formatted' => $activeCampaign->deadline ? FormatHelper::formatDateTime($activeCampaign->deadline, 'H:i') : '10:30',
                'sponsor_type' => $activeCampaign->sponsor_type ?: Campaign::SPONSOR_TYPE_NONE,
                'max_budget' => (int) ($activeCampaign->max_budget ?? 0),
                'sponsor_used' => $sponsorUsed,
                'popular_items' => $popularItems,
                'order_url' => route('user.campaigns.order-page', [$room->slug, $activeCampaign->id]),
                'has_ordered' => $roomUser->orders()
                    ->where('campaign_id', $activeCampaign->id)
                    ->where('status', '!=', OrderStatus::Cancelled->value)
                    ->exists(),
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
            'topSponsors' => $this->getTopSponsors($room),
            'weeklyItemTrend' => $this->getWeeklyItemTrend($room),
        ];
    }

    /**
     * Rank the room's actual sponsors (people/entities who funded a campaign) over the last 7
     * days, not the members who merely benefited from a sponsored order.
     *
     * @param Room $room Current active room instance.
     * @param int $limit Maximum number of sponsors to return.
     * @return array<int, array{name: string, amount: int, sponsored_campaigns: int}> Top sponsors, highest amount first.
     */
    private function getTopSponsors(Room $room, int $limit = 5): array
    {
        $from = Carbon::today()->subDays(6)->startOfDay();
        $to = Carbon::today()->endOfDay();

        return $this->sponsorLeaderboard->build($room, $from, $to, $limit)
            ->map(fn (array $row): array => [
                'name' => $row['user_name'],
                'amount' => $row['total_sponsored'],
                'sponsored_campaigns' => $row['sponsored_campaigns'],
            ])
            ->values()
            ->all();
    }

    /**
     * Aggregate daily order item count and order value for the last 7 days.
     *
     * @param Room $room Current active room instance.
     * @return array<int, array{date: string, day_name: string, items_count: int, value_amount: int}> One entry per day, oldest first.
     */
    private function getWeeklyItemTrend(Room $room): array
    {
        $today = Carbon::today();
        $dayLabels = [
            1 => __('room.dashboard.day_monday'),
            2 => __('room.dashboard.day_tuesday'),
            3 => __('room.dashboard.day_wednesday'),
            4 => __('room.dashboard.day_thursday'),
            5 => __('room.dashboard.day_friday'),
            6 => __('room.dashboard.day_saturday'),
            0 => __('room.dashboard.day_sunday'),
        ];

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $currentDate = $today->copy()->subDays($i);
            $dayOfWeek = (int) $currentDate->format('w');
            $dayLabel = $i === 0 ? __('room.dashboard.day_today') : ($dayLabels[$dayOfWeek] ?? $currentDate->format('D'));

            $dayOrders = Order::query()
                ->where('room_id', $room->id)
                ->whereDate('created_at', $currentDate)
                ->where('status', '!=', OrderStatus::Cancelled->value);

            $valueAmount = (int) (clone $dayOrders)->sum('final_amount');
            $itemsCount = (int) DB::table('order_items')
                ->joinSub((clone $dayOrders)->select('id'), 'day_orders', 'day_orders.id', '=', 'order_items.order_id')
                ->sum('order_items.quantity');

            $trend[] = [
                'date' => FormatHelper::formatDate($currentDate, 'd/m'),
                'day_name' => $dayLabel,
                'items_count' => $itemsCount,
                'value_amount' => $valueAmount,
            ];
        }

        return $trend;
    }
}
