<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\GlobalUser;
use App\Support\Helpers\FormatHelper;

class UserGlobalAnalyticsService
{
    /**
     * Tổng hợp và phân tích số liệu thống kê chi tiêu, tỷ lệ tài trợ, xu hướng chi tiêu 4 tuần và top món yêu thích cho toàn hệ thống.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @return array<string, mixed>  Mảng dữ liệu cho View thống kê
     */
    public function getAnalyticsViewData(GlobalUser $user): array
    {
        $roomUsers = $user->roomUsers()->with(['room', 'orders.campaign', 'orders.items'])->get();
        $orders = $roomUsers->pluck('orders')->flatten();
        $items = $orders->pluck('items')->flatten();

        $totalOrders = $orders->count();
        $totalSpent = (int) $orders->sum('final_amount');
        $avgSpent = $totalOrders > 0 ? (int) round($totalSpent / $totalOrders) : 0;
        $totalSponsor = (int) $orders->sum('sponsor_amount');
        $sponsorPercent = ($totalSpent + $totalSponsor) > 0 ? (int) round(($totalSponsor / ($totalSpent + $totalSponsor)) * 100) : 0;

        // Top favorite item
        $topItemRecord = $items->groupBy('item_name')->map(fn ($rows, $name) => [
            'name' => $name,
            'quantity' => (int) $rows->sum('quantity'),
        ])->sortByDesc('quantity')->first();

        $favoriteItem = $topItemRecord ? $topItemRecord['name'] : __('global.statistics.no_favorite_item');
        $favoriteItemOrders = $topItemRecord ? $topItemRecord['quantity'] : 0;

        // Weekly trend statistics from real orders
        $weeklyStats = [
            1 => ['label' => __('global.statistics.week_1_label'), 'spent' => 0, 'sponsor' => 0, 'height_spent' => 0, 'height_sponsor' => 0],
            2 => ['label' => __('global.statistics.week_2_label'), 'spent' => 0, 'sponsor' => 0, 'height_spent' => 0, 'height_sponsor' => 0],
            3 => ['label' => __('global.statistics.week_3_label'), 'spent' => 0, 'sponsor' => 0, 'height_spent' => 0, 'height_sponsor' => 0],
            4 => ['label' => __('global.statistics.week_4_label'), 'spent' => 0, 'sponsor' => 0, 'height_spent' => 0, 'height_sponsor' => 0],
        ];

        foreach ($orders as $o) {
            $day = (int) $o->created_at->format('j');
            $weekIdx = match(true) {
                $day <= 7 => 1,
                $day <= 14 => 2,
                $day <= 21 => 3,
                default => 4,
            };
            $weeklyStats[$weekIdx]['spent'] += (int) $o->final_amount;
            $weeklyStats[$weekIdx]['sponsor'] += (int) $o->sponsor_amount;
        }

        $allAmounts = array_merge(array_column($weeklyStats, 'spent'), array_column($weeklyStats, 'sponsor'));
        $maxWeeklySpent = max(1, ...$allAmounts);
        foreach ($weeklyStats as $k => $w) {
            $weeklyStats[$k]['height_spent'] = $w['spent'] > 0 ? max(8, (int) round(($w['spent'] / $maxWeeklySpent) * 85)) : 0;
            $weeklyStats[$k]['height_sponsor'] = $w['sponsor'] > 0 ? max(8, (int) round(($w['sponsor'] / $maxWeeklySpent) * 85)) : 0;
        }

        $peakWeek = collect($weeklyStats)->sortByDesc('spent')->first();
        $peakWeekText = ($peakWeek && $peakWeek['spent'] > 0)
            ? __('global.statistics.peak_week_text', [
                'amount' => FormatHelper::formatCurrency((int) $peakWeek['spent']),
                'suffix' => '',
                'week' => explode(' ', $peakWeek['label'])[0],
            ])
            : __('global.statistics.no_expense_in_period');

        // Category breakdown from real items
        $categoryStats = [];
        $totalItemQuantity = (int) $items->sum('quantity');
        if ($totalItemQuantity > 0) {
            $itemGroups = $items->groupBy('item_name')->map->sum('quantity')->sortByDesc(fn ($q) => $q);
            $colors = ['#006948', '#64748b', '#f59e0b', '#0284c7'];
            $idx = 0;
            foreach ($itemGroups->take(3) as $itemName => $qty) {
                $pct = (int) round(($qty / $totalItemQuantity) * 100);
                $categoryStats[] = [
                    'name' => $itemName,
                    'percent' => $pct,
                    'color' => $colors[$idx % count($colors)],
                    'quantity' => $qty,
                ];
                $idx++;
            }
        }

        // Spending by Room
        $roomStats = [];
        foreach ($roomUsers as $ru) {
            $rOrders = $ru->orders;
            if ($rOrders->isNotEmpty()) {
                $roomStats[] = [
                    'name' => $ru->room?->name ?? 'Room #' . $ru->room_id,
                    'location' => $ru->room?->description ?: __('global.statistics.default_workspace_desc'),
                    'order_count' => $rOrders->count(),
                    'spent' => (int) $rOrders->sum('final_amount'),
                    'sponsor' => (int) $rOrders->sum('sponsor_amount'),
                ];
            }
        }

        // Top Restaurant Brands
        $restaurantGrouping = $orders->filter(fn ($o) => !empty($o->campaign?->restaurant))->groupBy(fn ($o) => $o->campaign->restaurant);
        $topRestaurants = [];
        $rank = 1;
        foreach ($restaurantGrouping as $resName => $resOrders) {
            $topRestaurants[] = [
                'rank' => $rank++,
                'name' => $resName,
                'sub_title' => __('global.statistics.popular_beverages'),
                'order_count' => $resOrders->count(),
                'spent' => (int) $resOrders->sum('final_amount'),
            ];
            if ($rank > 4) break;
        }

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.statistics.breadcrumb_stats'), 'url' => route('user.me.statistics')],
        ];

        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->latest()->take(5)->get();

        return compact(
            'user',
            'totalOrders',
            'totalSpent',
            'avgSpent',
            'totalSponsor',
            'sponsorPercent',
            'favoriteItem',
            'favoriteItemOrders',
            'weeklyStats',
            'peakWeekText',
            'categoryStats',
            'roomStats',
            'topRestaurants',
            'breadcrumbs',
            'unreadNotificationsCount',
            'notifications'
        );
    }

    /**
     * Tính toán số liệu thống kê tổng hợp dạng JSON cho client API hoặc ứng dụng di động.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @return array<string, mixed>  Dữ liệu JSON tóm tắt (tổng đơn, tổng ly, chi tiêu, tài trợ, top món)
     */
    public function getAnalyticsApiData(GlobalUser $user): array
    {
        $orders = $user->roomUsers()->with('orders.items')->get()->pluck('orders')->flatten();
        $items = $orders->pluck('items')->flatten();

        return [
            'total_orders' => $orders->count(),
            'total_cups' => (int) $items->sum('quantity'),
            'total_amount' => (int) $orders->sum('final_amount'),
            'sponsor_received' => (int) $orders->sum('sponsor_amount'),
            'top_items' => $items->groupBy('item_name')->map(fn ($rows, $name) => [
                'name' => $name,
                'quantity' => (int) $rows->sum('quantity'),
            ])->sortByDesc('quantity')->values()->take(10),
        ];
    }
}
