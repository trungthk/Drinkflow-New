<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\CampaignStatus;
use App\Enums\PaymentAccountStatus;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Models\Debt;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    /**
     * Get aggregated data for Admin Dashboard Blade view.
     *
     * @param Room $room Room entity.
     * @param ?AdminAccount $admin Current logged-in admin account.
     * @return array<string, mixed> Aggregated page payload.
     */
    public function getDashboardPageData(Room $room, ?AdminAccount $admin): array
    {
        $assignedRooms = $admin?->rooms()->get() ?: collect([$room]);
        if ($assignedRooms->isEmpty()) {
            $assignedRooms = collect([$room]);
        }

        $metrics = $this->getDashboardMetrics($room);

        $liveOrders = Order::query()
            ->where('room_id', $room->id)
            ->with(['roomUser.globalUser', 'items.toppings'])
            ->latest()
            ->take(15)
            ->get();

        $activePaymentAccount = PaymentAccount::query()
            ->where('room_id', $room->id)
            ->where('status', PaymentAccountStatus::Active)
            ->first();

        return [
            'room' => $room,
            'assignedRooms' => $assignedRooms,
            'activeRoomsCount' => $metrics['active_rooms'],
            'ordersTodayCount' => $metrics['orders_today'],
            'ordersGrowth' => $metrics['orders_growth'],
            'todayTotalValue' => $metrics['today_total_value'],
            'todaySponsorValue' => $metrics['today_sponsor_value'],
            'outstandingDebtsTotal' => $metrics['outstanding_debts'],
            'outstandingDebtsCount' => $metrics['outstanding_debts_count'],
            'pendingDebtUsersCount' => $metrics['pending_debt_users_count'],
            'activeCampaignsCount' => $metrics['active_campaigns_count'],
            'activeParticipants' => $metrics['active_participants'],
            'debtCollectionRate' => $metrics['debt_collection_rate'],
            'activeCampaign' => $metrics['active_campaign'],
            'secondaryCampaign' => $metrics['secondary_campaign'],
            'weeklyTrend' => $metrics['weekly_trend'],
            'totalWeekCampaigns' => $metrics['weekly_total_campaigns'],
            'totalWeekSpending' => $metrics['weekly_total_spending'],
            'maxSpendingDayIndex' => $metrics['max_spending_day_index'],
            'liveOrders' => $liveOrders,
            'activePaymentAccount' => $activePaymentAccount,
        ];
    }

    /**
     * Calculate core metrics for both API and Page view.
     */
    public function getDashboardMetrics(Room $room): array
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $orders = Order::query()->where('room_id', $room->id);
        $debts = Debt::query()->where('room_id', $room->id);

        $campaignMetrics = fn ($query) => $query
            ->withCount('orders')
            ->withSum('orders as total_amount', 'final_amount')
            ->withSum('orders as sponsor_total', 'sponsor_amount');

        $activeCampaigns = $campaignMetrics($room->campaigns()->where('status', CampaignStatus::Active))
            ->latest('started_at')
            ->get();

        $activeCampaign = $activeCampaigns->first();
        $secondaryCampaign = $activeCampaigns->count() > 1 ? $activeCampaigns->get(1) : null;

        $ordersTodayCount = (clone $orders)->whereDate('created_at', $today)->count();
        $ordersYesterdayCount = (clone $orders)->whereDate('created_at', $yesterday)->count();
        $ordersGrowth = $ordersYesterdayCount > 0
            ? round((($ordersTodayCount - $ordersYesterdayCount) / $ordersYesterdayCount) * 100)
            : ($ordersTodayCount > 0 ? 100 : 0);

        $todayTotalValue = (int) (clone $orders)->whereDate('created_at', $today)->whereNotIn('status', ['cancelled'])->sum('final_amount');
        $todaySponsorValue = (int) (clone $orders)->whereDate('created_at', $today)->sum('sponsor_amount');

        $pendingDebtUsersCount = (clone $debts)->whereIn('status', ['unpaid', 'partial'])->distinct('room_user_id')->count('room_user_id');

        // Weekly trend for 7 days
        $weeklyTrend = [];
        $maxSpending = 0;
        $maxDayIndex = -1;
        $totalWeekCampaigns = 0;
        $totalWeekSpending = 0;

        $dayLabels = [
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            0 => 'Chủ Nhật',
        ];

        for ($i = 6; $i >= 0; $i--) {
            $currentDate = $today->copy()->subDays($i);
            $dayOfWeek = (int) $currentDate->format('w');
            $dayLabel = $i === 0 ? 'Hôm nay' : ($dayLabels[$dayOfWeek] ?? $currentDate->format('D'));

            $cCount = $room->campaigns()->whereDate('created_at', $currentDate)->count();
            $sAmount = (int) Order::query()
                ->where('room_id', $room->id)
                ->whereDate('created_at', $currentDate)
                ->whereNotIn('status', ['cancelled'])
                ->sum('final_amount');

            $totalWeekCampaigns += $cCount;
            $totalWeekSpending += $sAmount;

            if ($sAmount > $maxSpending) {
                $maxSpending = $sAmount;
                $maxDayIndex = 6 - $i;
            }

            $weeklyTrend[] = [
                'date' => $currentDate->format('d/m'),
                'day_name' => $dayLabel,
                'campaigns_count' => $cCount,
                'spending_amount' => $sAmount,
            ];
        }

        // Active participants in active campaigns
        $activeParticipants = 0;
        if ($activeCampaign) {
            $activeParticipants = Order::query()
                ->where('campaign_id', $activeCampaign->id)
                ->distinct('room_user_id')
                ->count('room_user_id');
        }

        // Debt collection rate
        $totalDebtsAmount = (int) (clone $debts)->sum('original_amount');
        $paidDebtsAmount = (int) (clone $debts)->sum('paid_amount');
        $debtCollectionRate = $totalDebtsAmount > 0
            ? round(($paidDebtsAmount / $totalDebtsAmount) * 100, 1)
            : 100;

        return [
            'active_rooms' => 1,
            'orders_today' => $ordersTodayCount,
            'orders_growth' => $ordersGrowth,
            'today_total_value' => $todayTotalValue,
            'today_sponsor_value' => $todaySponsorValue,
            'outstanding_debts' => (int) (clone $debts)->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount'),
            'outstanding_debts_count' => (clone $debts)->whereIn('status', ['unpaid', 'partial'])->count(),
            'pending_debt_users_count' => $pendingDebtUsersCount,
            'active_campaigns' => $activeCampaigns->count(),
            'active_campaigns_count' => $activeCampaigns->count(),
            'active_participants' => $activeParticipants,
            'debt_collection_rate' => $debtCollectionRate,
            'active_campaign' => $activeCampaign,
            'secondary_campaign' => $secondaryCampaign,
            'weekly_trend' => $weeklyTrend,
            'weekly_total_campaigns' => $totalWeekCampaigns,
            'weekly_total_spending' => $totalWeekSpending,
            'max_spending_day_index' => $maxDayIndex,
        ];
    }

    /**
     * Get data for Admin Operations Management Hub (/admin/{room}/manage).
     *
     * @param Room $room Room entity.
     * @param ?AdminAccount $admin Current logged-in admin account.
     * @return array<string, mixed> Manage page payload.
     */
    public function getManagePageData(Room $room, ?AdminAccount $admin): array
    {
        $assignedRooms = $admin?->rooms()->get() ?: collect([$room]);
        if ($assignedRooms->isEmpty()) {
            $assignedRooms = collect([$room]);
        }

        $activePaymentAccount = PaymentAccount::query()
            ->where('room_id', $room->id)
            ->where('status', PaymentAccountStatus::Active)
            ->first();

        $allPaymentAccounts = PaymentAccount::query()
            ->where('room_id', $room->id)
            ->latest()
            ->get();

        $activeCampaign = $room->campaigns()
            ->where('status', CampaignStatus::Active)
            ->with(['items.sizes', 'items.toppings'])
            ->latest('started_at')
            ->first();

        $debts = Debt::query()
            ->where('room_id', $room->id)
            ->with(['roomUser.globalUser', 'campaign'])
            ->latest()
            ->get();

        $roomUsers = $room->roomUsers()
            ->with(['globalUser', 'devices'])
            ->withCount(['orders', 'debts'])
            ->latest('last_active_at')
            ->get();

        $notificationChannels = $room->notificationChannels()->get();
        $roomSettings = $room->roomSettings()->pluck('value', 'key')->all();

        $totalDebtsAmount = (int) $debts->sum('original_amount');
        $unpaidDebtsAmount = (int) $debts->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount');
        $paidDebtsAmount = (int) $debts->sum('paid_amount');
        $debtUsersCount = $debts->whereIn('status', ['unpaid', 'partial'])->pluck('room_user_id')->unique()->count();

        return [
            'room' => $room,
            'assignedRooms' => $assignedRooms,
            'activePaymentAccount' => $activePaymentAccount,
            'allPaymentAccounts' => $allPaymentAccounts,
            'activeCampaign' => $activeCampaign,
            'debts' => $debts,
            'roomUsers' => $roomUsers,
            'notificationChannels' => $notificationChannels,
            'roomSettings' => $roomSettings,
            'debtStats' => [
                'total_amount' => $totalDebtsAmount,
                'unpaid_amount' => $unpaidDebtsAmount,
                'paid_amount' => $paidDebtsAmount,
                'users_count' => $debtUsersCount,
            ],
        ];
    }
}
