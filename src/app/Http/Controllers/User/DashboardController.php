<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Invoke the controller.
     * @param Request $request Parameter value.
     * @return View Result of the operation.
     */
    public function __invoke(Request $request): View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $activeCampaign = $room->campaigns()->where('status', 'active')->with(['items.sizes', 'items.toppings'])->first();
        
        $campaignData = null;
        if ($activeCampaign) {
            $totalMembers = $room->roomUsers()->where('status', 'active')->count();
            $orderedMembersCount = Order::where('campaign_id', $activeCampaign->id)->distinct('room_user_id')->count('room_user_id');
            $totalPoolValue = Order::where('campaign_id', $activeCampaign->id)->sum('final_amount');
            $sponsorUsed = Order::where('campaign_id', $activeCampaign->id)->sum('sponsor_amount');
            $sponsorBudget = 200000; // default or from campaign/room settings
            $sponsorRemaining = max(0, $sponsorBudget - $sponsorUsed);
            $sponsorPercent = $sponsorBudget > 0 ? min(100, round(($sponsorUsed / $sponsorBudget) * 100, 1)) : 0;

            $recommendedItems = $activeCampaign->items()->where('status', 'active')->take(6)->get();

            $campaignData = [
                'id' => $activeCampaign->id,
                'name' => $activeCampaign->name,
                'restaurant' => $activeCampaign->restaurant ?? 'Đối tác',
                'creator_name' => 'Admin',
                'description' => $activeCampaign->description ?? '',
                'deadline' => $activeCampaign->deadline,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
                'deadline_formatted' => $activeCampaign->deadline ? $activeCampaign->deadline->format('H:i') : '10:30',
                'sponsor_budget' => $sponsorBudget,
                'sponsor_remaining' => $sponsorRemaining,
                'sponsor_used' => $sponsorUsed,
                'sponsor_percent' => $sponsorPercent,
                'total_members' => max(1, $totalMembers),
                'ordered_members' => $orderedMembersCount,
                'total_pool_value' => $totalPoolValue,
                'recommended_items' => $recommendedItems,
                'order_url' => route('user.campaigns.order-page', [$room->slug, $activeCampaign->id]),
            ];
        }

        // Unpaid debts in this room
        $unpaidDebts = Debt::where('room_user_id', $roomUser->id)
            ->where('room_id', $room->id)
            ->where('status', 'unpaid')
            ->sum('remaining_amount');

        // Recent orders by this user in this room
        $userRecentOrders = $roomUser->orders()->with('items')->latest()->take(5)->get();

        // All active rooms of user for dropdown
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();

        // Unread notifications count
        $unreadCount = DB::table('user_notifications')
            ->where('global_user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return view('user.dashboard', [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'activeCampaign' => $campaignData,
            'unpaidDebts' => (int) $unpaidDebts,
            'userRecentOrders' => $userRecentOrders,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ]);
    }
}
