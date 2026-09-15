<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Support\Helpers\FormatHelper;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomUserStatus;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

class UserGlobalDashboardService
{
    /**
     * Tổng hợp toàn bộ dữ liệu thống kê, phòng gần đây, đơn hàng gần đây và thông báo cho Dashboard người dùng toàn hệ thống.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống đang đăng nhập
     * @return array<string, mixed>  Mảng dữ liệu cho View Dashboard
     */
    public function getDashboardData(GlobalUser $user): array
    {
        // Extract Workspace domain from user email
        $domain = Str::after((string) $user->email, '@');
        $workspaceText = __('global.dashboard.workspace_auth_text', ['domain' => $domain ?: 'company.com']);

        // Query active room users of current user
        $roomUsers = $user->roomUsers()
            ->with([
                'room' => function ($q) {
                    $q->withCount(['roomUsers' => function ($ru) {
                        $ru->where('status', RoomUserStatus::Active->value);
                    }]);
                    $q->with(['campaigns' => function ($c) {
                        $c->where('status', CampaignStatus::Active->value)->latest();
                    }]);
                },
            ])
            ->where('status', RoomUserStatus::Active->value)
            ->orderByDesc('last_active_at')
            ->orderByDesc('updated_at')
            ->get();

        $roomsCount = $roomUsers->count();
        $roomUserIds = $user->roomUsers()->pluck('id');

        // Order metrics across all user memberships
        $ordersQuery = Order::query()->whereIn('room_user_id', $roomUserIds);
        $totalOrdersCount = (clone $ordersQuery)->count();

        $completedStatuses = [
            OrderStatus::Submitted->value,
            OrderStatus::Confirmed->value,
            OrderStatus::Completed->value,
            'submitted',
            'confirmed',
            'paid',
            'completed',
        ];

        $totalSpent = (int) (clone $ordersQuery)->whereIn('status', $completedStatuses)->sum('final_amount');
        $sponsorReceived = (int) (clone $ordersQuery)->whereIn('status', $completedStatuses)->sum('sponsor_amount');
        $savingsPercent = ($totalSpent + $sponsorReceived) > 0
            ? (int) round(($sponsorReceived / ($totalSpent + $sponsorReceived)) * 100)
            : 0;

        // Recent rooms (up to 4)
        $recentRooms = $roomUsers->take(4)->map(function ($ru) {
            $room = $ru->room;
            $activeCampaign = $room?->campaigns->first();

            $campaignData = null;
            if ($activeCampaign) {
                $cupsCollected = (int) OrderItem::query()
                    ->whereIn('order_id', function ($q) use ($activeCampaign) {
                        $q->select('id')->from('orders')
                            ->where('campaign_id', $activeCampaign->id)
                            ->whereIn('status', ['submitted', 'confirmed', 'paid', 'completed']);
                    })
                    ->sum('quantity');

                $targetCups = 20;
                $progressPercent = min(100, (int) round(($cupsCollected / $targetCups) * 100));

                $timeRemaining = __('global.dashboard.campaign_open');
                if ($activeCampaign->deadline) {
                    if ($activeCampaign->deadline->isFuture()) {
                        $diff = now()->diff($activeCampaign->deadline);
                        $timeRemaining = sprintf('%02d:%02d:%02d', $diff->h + ($diff->days * 24), $diff->i, $diff->s);
                    } else {
                        $timeRemaining = __('global.dashboard.campaign_expired');
                    }
                }

                $discountText = __('global.dashboard.free_ship');
                if ($activeCampaign->discount > 0) {
                    $discountText = '-' . FormatHelper::formatCurrency((int) $activeCampaign->discount);
                }

                $campaignData = [
                    'id' => $activeCampaign->id,
                    'name' => $activeCampaign->name,
                    'restaurant' => $activeCampaign->restaurant ?: __('global.dashboard.default_beverage_shop'),
                    'deadline_formatted' => $activeCampaign->deadline ? FormatHelper::formatDateTime($activeCampaign->deadline, 'H:i') : '10:30',
                    'time_remaining' => $timeRemaining,
                    'cups_collected' => $cupsCollected,
                    'target_cups' => $targetCups,
                    'progress_percent' => $progressPercent,
                    'discount_text' => $discountText,
                    'sponsor_note' => $activeCampaign->sponsor_name ? __('global.dashboard.fund_label', ['name' => $activeCampaign->sponsor_name]) : __('global.dashboard.default_tech_fund'),
                    'order_url' => route('user.campaigns.order-page', ['room' => $room->id, 'campaign' => $activeCampaign->id]),
                ];
            }

            return [
                'id' => $room?->id,
                'name' => $room?->name ?? 'Room',
                'description' => $room?->description ?: __('global.dashboard.default_room_desc'),
                'member_count' => $room?->room_users_count ?? 1,
                'user_code' => $ru->user_code,
                'campaign' => $campaignData,
                'room_url' => $room ? route('user.dashboard', $room) : '#',
            ];
        });

        // Recent orders (up to 5)
        $recentOrders = (clone $ordersQuery)
            ->with(['room', 'campaign', 'items'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                $statusVal = $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status;
                $isPaid = in_array($statusVal, ['paid', 'completed']);
                $isPending = in_array($statusVal, ['submitted', 'confirmed']);

                $itemsText = $order->items->map(function ($item) {
                    $txt = $item->item_name;
                    if ($item->size_name) {
                        $txt .= ' (' . $item->size_name . ')';
                    }
                    $txt .= ' x' . $item->quantity;
                    if ($item->note) {
                        $txt .= ', ' . $item->note;
                    }
                    return $txt;
                })->join('; ');

                if (empty($itemsText)) {
                    $itemsText = __('global.dashboard.default_item_text');
                }

                $timeText = FormatHelper::formatDateTime($order->created_at, 'd/m, H:i');
                if ($order->created_at->isToday()) {
                    $timeText = __('global.dashboard.today_at', ['time' => FormatHelper::formatDateTime($order->created_at, 'H:i')]);
                } elseif ($order->created_at->isYesterday()) {
                    $timeText = __('global.dashboard.yesterday_at', ['time' => FormatHelper::formatDateTime($order->created_at, 'H:i')]);
                }

                return [
                    'id' => $order->id,
                    'code' => '#ORD-' . str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
                    'time_formatted' => $timeText,
                    'room_name' => $order->room?->name ?? 'DrinkFlow Room',
                    'restaurant' => $order->campaign?->restaurant ?: 'Cửa hàng',
                    'items_summary' => $itemsText,
                    'final_amount' => $order->final_amount,
                    'final_amount_formatted' => FormatHelper::formatCurrency((int) $order->final_amount),
                    'is_paid' => $isPaid,
                    'is_pending' => $isPending,
                    'status_label' => $isPaid ? __('global.dashboard.paid') : ($isPending ? __('global.dashboard.unpaid') : __('global.common.cancelled')),
                    'detail_url' => $order->room_id ? route('user.orders.page', ['room' => $order->room_id, 'order' => $order->id]) : '#',
                ];
            });

        // Notifications
        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->latest()->take(5)->get();

        return [
            'user' => $user,
            'workspaceText' => $workspaceText,
            'roomsCount' => $roomsCount,
            'totalOrdersCount' => $totalOrdersCount,
            'totalSpent' => $totalSpent,
            'sponsorReceived' => $sponsorReceived,
            'savingsPercent' => $savingsPercent,
            'recentRooms' => $recentRooms,
            'recentOrders' => $recentOrders,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'notifications' => $notifications,
        ];
    }
}
