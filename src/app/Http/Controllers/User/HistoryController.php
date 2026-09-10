<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roomUserIds = $request->attributes->get('global_user')->roomUsers()->pluck('id');
        $query = Order::query()->whereIn('room_user_id', $roomUserIds)->with(['items.toppings', 'campaign', 'room'])->latest();
        if ($request->filled('room_id')) {
            $query->where('room_id', (int) $request->input('room_id'));
        }
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', (int) $request->input('campaign_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return response()->json(['data' => $query->paginate(20)]);
    }
}
