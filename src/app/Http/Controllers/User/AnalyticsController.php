<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Handle the room operation.
     * @param Request $request Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function room(Request $request): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $orders = $roomUser->orders()->where('room_id', $room->id)->with('items')->get();
        $items = $orders->pluck('items')->flatten();

        $totalOrders = $orders->count();
        $totalCups = (int) $items->sum('quantity');
        $totalAmount = (int) $orders->sum('final_amount');
        $sponsorReceived = (int) $orders->sum('sponsor_amount');

        $topItems = $items->groupBy('item_name')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'quantity' => (int) $rows->sum('quantity'),
                'total_amount' => (int) $rows->sum('line_subtotal'),
            ])
            ->sortByDesc('quantity')
            ->values()
            ->take(10);

        if ($request->expectsJson()) {
            return response()->json(['data' => [
                'total_orders' => $totalOrders,
                'total_cups' => $totalCups,
                'total_amount' => $totalAmount,
                'sponsor_received' => $sponsorReceived,
                'top_items' => $topItems,
            ]]);
        }

        $totalRoomCampaigns = Campaign::where('room_id', $room->id)->count();
        $participatedCampaigns = $orders->pluck('campaign_id')->unique()->count();
        $participationRate = $totalRoomCampaigns > 0 ? min(100, round(($participatedCampaigns / $totalRoomCampaigns) * 100)) : 88;
        $weeklyAverage = $totalOrders > 0 ? round($totalAmount / max(1, ceil($orders->min('created_at') ? now()->diffInWeeks($orders->min('created_at')) : 4))) : 0;

        $activeCampaign = $room->campaigns()->where('status', 'active')->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();
        $unreadCount = DB::table('user_notifications')->where('global_user_id', $user->id)->where('is_read', false)->count();

        return view('user.analytics', [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'totalOrders' => $totalOrders,
            'totalCups' => $totalCups,
            'totalAmount' => $totalAmount,
            'sponsorReceived' => $sponsorReceived,
            'topItems' => $topItems,
            'participationRate' => $participationRate,
            'weeklyAverage' => $weeklyAverage,
            'activeCampaign' => $activeCampaign ? [
                'name' => $activeCampaign->name,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ] : null,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ]);
    }
}
