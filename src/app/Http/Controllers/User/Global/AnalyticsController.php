<?php

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Handle global analytics request (Web or JSON API).
     *
     * @param Request $request
     * @return JsonResponse|View
     */
    public function __invoke(Request $request): JsonResponse|View
    {
        if ($request->expectsJson() || $request->routeIs('user.analytics.global')) {
            return $this->global($request);
        }

        return $this->view($request);
    }

    /**
     * Return Web View for /me/statistics.
     *
     * @param Request $request
     * @return View
     */
    public function view(Request $request): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

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
                'amount' => number_format($peakWeek['spent'], 0, ',', '.'),
                'suffix' => __('global.common.money_suffix'),
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

        return view('user.global.statistics', compact(
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
        ));
    }

    /**
     * Handle global analytics aggregation (JSON API).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function global(Request $request): JsonResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');
        $orders = $user->roomUsers()->with('orders.items')->get()->pluck('orders')->flatten();
        $items = $orders->pluck('items')->flatten();

        return response()->json(['data' => [
            'total_orders' => $orders->count(),
            'total_cups' => (int) $items->sum('quantity'),
            'total_amount' => (int) $orders->sum('final_amount'),
            'sponsor_received' => (int) $orders->sum('sponsor_amount'),
            'top_items' => $items->groupBy('item_name')->map(fn ($rows, $name) => [
                'name' => $name,
                'quantity' => (int) $rows->sum('quantity'),
            ])->sortByDesc('quantity')->values()->take(10),
        ]]);
    }
}
