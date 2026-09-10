<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Handle the global operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function global(Request $request): JsonResponse
    {
        $user = $request->attributes->get('global_user');
        $orders = $user->roomUsers()->with('orders.items')->get()->pluck('orders')->flatten();
        $items = $orders->pluck('items')->flatten();

        return response()->json(['data' => [
            'total_orders' => $orders->count(),
            'total_cups' => (int) $items->sum('quantity'),
            'total_amount' => (int) $orders->sum('final_amount'),
            'sponsor_received' => (int) $orders->sum('sponsor_amount'),
            'top_items' => $items->groupBy('item_name')->map(fn ($rows, $name) => ['name' => $name, 'quantity' => (int) $rows->sum('quantity')])->sortByDesc('quantity')->values()->take(10),
        ]]);
    }

    /**
     * Handle the room operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function room(Request $request): JsonResponse
    {
        $roomUser = $request->attributes->get('room_user');
        $orders = $roomUser->orders()->with('items')->get();
        $items = $orders->pluck('items')->flatten();

        return response()->json(['data' => [
            'total_orders' => $orders->count(),
            'total_cups' => (int) $items->sum('quantity'),
            'total_amount' => (int) $orders->sum('final_amount'),
            'sponsor_received' => (int) $orders->sum('sponsor_amount'),
            'top_items' => $items->groupBy('item_name')->map(fn ($rows, $name) => ['name' => $name, 'quantity' => (int) $rows->sum('quantity')])->sortByDesc('quantity')->values()->take(10),
        ]]);
    }
}
