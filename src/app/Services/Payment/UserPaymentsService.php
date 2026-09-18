<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Models\GlobalUser;
use App\Models\Order;
use Illuminate\Http\Request;

class UserPaymentsService
{
    public function __construct(private readonly VietQrService $vietQr) {}
    /**
     * Thu thập và tổng hợp dữ liệu đối soát công nợ, đơn hàng chưa trả/đã trả và tài khoản nhận tiền VietQR cho trang thanh toán cá nhân.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @return array<string, mixed>  Mảng dữ liệu cho View đối soát thanh toán
     */
    public function getPaymentsData(GlobalUser $user, Request $request): array
    {
        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->whereNull('read_at')->latest()->take(5)->get();

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.orders.payments_cta'), 'url' => route('user.me.payments')],
        ];

        $roomUserIds = $user->roomUsers()
            ->where('status', \App\Enums\RoomUserStatus::Active->value)
            ->pluck('id');

        // Fetch all orders for current user
        $allOrders = Order::query()
            ->whereIn('room_user_id', $roomUserIds)
            ->with(['room.paymentAccounts', 'campaign.paymentAccount', 'items'])
            ->latest()
            ->get();

        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth();

        // Separate into Unpaid and Paid
        $unpaidStatuses = [OrderStatus::Submitted->value, OrderStatus::Confirmed->value, 'pending'];
        $paidStatuses = [OrderStatus::Completed->value];

        $unpaidOrders = $allOrders->filter(fn($o) => in_array((string) (is_object($o->status) ? $o->status->value : $o->status), $unpaidStatuses));
        $paidOrders = $allOrders->filter(fn($o) => in_array((string) (is_object($o->status) ? $o->status->value : $o->status), $paidStatuses));

        // Overview Metrics
        $totalUnpaidAmount = (int) $unpaidOrders->sum('final_amount');
        $unpaidCount = $unpaidOrders->count();

        $paidThisMonthOrders = $paidOrders->filter(fn($o) => $o->created_at && $o->created_at >= $thisMonthStart);
        $paidThisMonthAmount = (int) $paidThisMonthOrders->sum('final_amount');
        $paidThisMonthCount = $paidThisMonthOrders->count();

        // If no paid orders this month, fallback to total paid to display realistic data
        if ($paidThisMonthCount === 0 && $paidOrders->isNotEmpty()) {
            $paidThisMonthAmount = (int) $paidOrders->sum('final_amount');
            $paidThisMonthCount = $paidOrders->count();
        }

        $totalSponsorReceived = (int) $paidOrders->filter(fn($o) => $o->created_at && $o->created_at >= $thisMonthStart)->sum('sponsor_amount');
        if ($totalSponsorReceived === 0) {
            $totalSponsorReceived = (int) $paidOrders->sum('sponsor_amount');
        }

        // Active Tab Filter: 'all', 'unpaid', 'paid'
        $activeFilter = $request->input('filter', 'all');
        $sort = $request->input('sort', 'due_asc');

        if ($activeFilter === 'unpaid') {
            // The payment statement list is intentionally limited to completed orders.
            $displayedOrders = collect();
        } else {
            $displayedOrders = $paidOrders;
        }

        // Sorting
        if ($sort === 'amount_desc') {
            $displayedOrders = $displayedOrders->sortByDesc('final_amount');
        } elseif ($sort === 'amount_asc') {
            $displayedOrders = $displayedOrders->sortBy('final_amount');
        } else {
            // Default: newest first or unpaid prioritized
            $displayedOrders = $displayedOrders->sortBy(function ($item) {
                $st = (string) (is_object($item->status) ? $item->status->value : $item->status);
                return in_array($st, [OrderStatus::Submitted->value, OrderStatus::Confirmed->value]) ? 0 : 1;
            });
        }

        // Default Payment Account for VietQR
        $primaryRoom = $user->roomUsers()->with('room.paymentAccounts')->where('status', \App\Enums\RoomUserStatus::Active->value)->first()?->room;
        $defaultPayment = $primaryRoom?->paymentAccounts->firstWhere('is_default', true)
            ?? $primaryRoom?->paymentAccounts->first();

        $defaultBank = $defaultPayment ? [
            'bank_name' => $defaultPayment->bank_name,
            'bank_code' => $defaultPayment->bank_code,
            'account_number' => $defaultPayment->getRawOriginal('account_number'),
            'account_name' => $defaultPayment->account_name,
        ] : [
            'bank_name' => __('global.payments.no_bank_configured'),
            'bank_code' => '',
            'account_number' => '',
            'account_name' => '',
        ];

        $qrPayloads = [];
        foreach ($allOrders as $order) {
            $account = $order->campaign?->paymentAccount
                ?? $order->room?->paymentAccounts?->firstWhere('is_default', true)
                ?? $order->room?->paymentAccounts?->first();
            if ($account) {
                $qrPayloads[$order->id] = $this->vietQr->generate(
                    $account,
                    (int) $order->final_amount,
                    (string) $order->code,
                );
            }
        }

        return [
            'user' => $user,
            'allOrders' => $allOrders,
            'displayedOrders' => $displayedOrders,
            'totalCount' => $displayedOrders->count(),
            'unpaidCount' => $unpaidCount,
            'paidCount' => $paidOrders->count(),
            'totalUnpaidAmount' => $totalUnpaidAmount,
            'paidThisMonthAmount' => $paidThisMonthAmount,
            'paidThisMonthCount' => $paidThisMonthCount,
            'totalSponsorReceived' => $totalSponsorReceived,
            'activeFilter' => $activeFilter,
            'sort' => $sort,
            'defaultBank' => $defaultBank,
            'qrPayloads' => $qrPayloads,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'notifications' => $notifications,
            'breadcrumbs' => $breadcrumbs,
        ];
    }
}
