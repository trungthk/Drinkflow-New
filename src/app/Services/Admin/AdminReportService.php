<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Campaign;
use App\Models\Debt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminReportService
{
    /**
     * Compute comprehensive report analytics and popular trends for a room.
     *
     * @param Request $request Incoming HTTP request containing period or date range.
     * @param Room $room Room entity.
     * @return array<string, mixed> Analytics payload.
     */
    public function getReportMetrics(Request $request, Room $room): array
    {
        [$from, $to] = $this->period($request, $room->timezone ?? config('app.timezone'));

        $orders = Order::query()
            ->where('orders.room_id', $room->id)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', ['cancelled']);

        $campaigns = Campaign::query()
            ->where('campaigns.room_id', $room->id)
            ->whereBetween('campaigns.created_at', [$from, $to]);

        $debts = Debt::query()
            ->where('debts.room_id', $room->id)
            ->whereBetween('debts.created_at', [$from, $to]);

        $orderIds = (clone $orders)->pluck('id');

        $popularDrinks = $orderIds->isEmpty()
            ? collect()
            : OrderItem::query()
                ->whereIn('order_id', $orderIds)
                ->selectRaw('item_name, SUM(quantity) as quantity')
                ->groupBy('item_name')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get();

        $popularStores = (clone $orders)
            ->join('campaigns', 'campaigns.id', '=', 'orders.campaign_id')
            ->selectRaw('campaigns.restaurant, COUNT(orders.id) as orders, SUM(orders.final_amount) as spending')
            ->groupBy('campaigns.restaurant')
            ->orderByDesc('orders')
            ->limit(10)
            ->get();

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'campaign_count' => (clone $campaigns)->count(),
            'order_count' => (clone $orders)->count(),
            'spending' => (int) (clone $orders)->sum('final_amount'),
            'sponsor_amount' => (int) (clone $orders)->sum('sponsor_amount'),
            'debt' => (int) (clone $debts)->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount'),
            'user_participation' => (int) (clone $orders)->distinct('room_user_id')->count('room_user_id'),
            'popular_drinks' => $popularDrinks,
            'popular_stores' => $popularStores,
        ];
    }

    /**
     * Compute date period bounds based on request parameters.
     *
     * @param Request $request Incoming request.
     * @param string $timezone Timezone string.
     * @return array{0: Carbon, 1: Carbon} Start and end dates.
     */
    public function period(Request $request, string $timezone): array
    {
        $now = Carbon::now($timezone ?: config('app.timezone'));

        if ($request->filled('from') || $request->filled('to')) {
            return [
                Carbon::parse($request->input('from', $now->copy()->startOfDay()), $timezone)->startOfDay(),
                Carbon::parse($request->input('to', $now), $timezone)->endOfDay(),
            ];
        }

        $start = match ($request->string('period', 'month')->toString()) {
            'today' => $now->copy()->startOfDay(),
            'week' => $now->copy()->startOfWeek(),
            'quarter' => $now->copy()->firstOfQuarter()->startOfDay(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        return [$start, $now->copy()->endOfDay()];
    }
}
