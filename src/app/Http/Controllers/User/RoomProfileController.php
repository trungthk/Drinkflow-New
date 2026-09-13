<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomProfileController extends Controller
{
    public function index(Request $request, Room $room): View|JsonResponse
    {
        $room = $request->attributes->get('room') ?? $room;
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $orders = $roomUser->orders()->with('items')->get();
        $totalOrders = $orders->count();
        $totalSpent = (int) $orders->sum('final_amount');
        $totalSponsor = (int) $orders->sum('sponsor_amount');
        $items = $orders->pluck('items')->flatten();
        $favoriteItems = $items->groupBy('item_name')
            ->map(fn ($rows, $name) => ['name' => $name, 'count' => (int) $rows->sum('quantity')])
            ->sortByDesc('count')
            ->take(5);

        $unreadCount = DB::table('user_notifications')
            ->where('global_user_id', $user->id)
            ->where('is_read', false)
            ->count();

        if ($request->expectsJson()) {
            return response()->json([
                'room_user' => $roomUser,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'total_spent' => $totalSpent,
                    'total_sponsor' => $totalSponsor,
                ],
                'favorite_items' => $favoriteItems,
            ]);
        }

        $activeCampaign = $room->campaigns()->where('status', 'active')->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();

        return view('user.profile', [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'totalOrders' => $totalOrders,
            'totalSpent' => $totalSpent,
            'totalSponsor' => $totalSponsor,
            'favoriteItems' => $favoriteItems,
            'unreadNotificationsCount' => $unreadCount,
            'activeCampaign' => $activeCampaign ? [
                'name' => $activeCampaign->name,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ] : null,
            'userRooms' => $userRooms,
        ]);
    }
}
