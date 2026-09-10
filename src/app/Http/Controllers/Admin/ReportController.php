<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        [$from, $to] = $this->period($request, $room->timezone);
        $orders = Order::query()->where('orders.room_id', $room->id)->whereBetween('orders.created_at', [$from, $to])->whereNotIn('orders.status', ['cancelled']);
        $campaigns = Campaign::query()->where('campaigns.room_id', $room->id)->whereBetween('campaigns.created_at', [$from, $to]);
        $debts = Debt::query()->where('debts.room_id', $room->id)->whereBetween('debts.created_at', [$from, $to]);
        $orderIds = (clone $orders)->pluck('id');

        $popularDrinks = $orderIds->isEmpty() ? collect() : \App\Models\OrderItem::query()->whereIn('order_id', $orderIds)->selectRaw('item_name, SUM(quantity) as quantity')->groupBy('item_name')->orderByDesc('quantity')->limit(10)->get();
        $popularStores = (clone $orders)->join('campaigns', 'campaigns.id', '=', 'orders.campaign_id')->selectRaw('campaigns.restaurant, COUNT(orders.id) as orders, SUM(orders.final_amount) as spending')->groupBy('campaigns.restaurant')->orderByDesc('orders')->limit(10)->get();

        return response()->json(['data' => [
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
        ]]);
    }

    /**
     * Handle the period operation.
     * @param Request $request Parameter value.
     * @param string $timezone Parameter value.
     * @return array Result of the operation.
     */
    private function period(Request $request, string $timezone): array
    {
        $now = Carbon::now($timezone ?: config('app.timezone'));
        if ($request->filled('from') || $request->filled('to')) {
            return [Carbon::parse($request->input('from', $now->copy()->startOfDay()), $timezone)->startOfDay(), Carbon::parse($request->input('to', $now), $timezone)->endOfDay()];
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
