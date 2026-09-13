<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function index(Request $request): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $query = Campaign::query()->where('room_id', $room->id)->whereIn('status', ['active', 'scheduled']);
        $campaigns = $query->with(['items' => function ($q) use ($request) {
            $q->where('status', 'active')->when($request->filled('q'), fn ($items) => $items->where(function ($search) use ($request) {
                $term = trim($request->string('q')->toString());
                $search->where('name', 'like', "%{$term}%")->orWhere('normalized_name', 'like', '%'.strtoupper(Str::ascii($term)).'%');
            }))->when($request->filled('category'), fn ($items) => $items->where('category', $request->string('category')))->with(['sizes', 'toppings']);
        }])->when($request->filled('q'), fn ($campaigns) => $campaigns->whereHas('items', fn ($items) => $items->where('status', 'active')->where(function ($search) use ($request) {
            $term = trim($request->string('q')->toString());
            $search->where('name', 'like', "%{$term}%")->orWhere('normalized_name', 'like', '%'.strtoupper(Str::ascii($term)).'%');
        })))->orderBy('deadline')->paginate(20);

        if ($request->expectsJson() || $request->filled('q')) {
            return response()->json(['data' => $campaigns]);
        }

        $activeCampaign = $room->campaigns()->where('status', 'active')->with(['items' => function ($q) {
            $q->where('status', 'active')->with(['sizes', 'toppings']);
        }])->first();

        $categories = collect();
        $activeUserOrder = null;
        $campaignStats = null;

        if ($activeCampaign) {
            $categories = $activeCampaign->items->pluck('category')->filter()->unique()->values();
            $activeUserOrder = $roomUser ? $roomUser->orders()
                ->where('campaign_id', $activeCampaign->id)
                ->whereIn('status', ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering'])
                ->with('items.toppings')
                ->latest()
                ->first() : null;

            $totalMembers = $room->roomUsers()->where('status', 'active')->count();
            $orderedMembersCount = Order::where('campaign_id', $activeCampaign->id)->distinct('room_user_id')->count('room_user_id');
            $totalPoolValue = Order::where('campaign_id', $activeCampaign->id)->sum('final_amount');
            $participants = Order::where('campaign_id', $activeCampaign->id)
                ->with('roomUser.globalUser')
                ->latest()
                ->take(10)
                ->get()
                ->pluck('roomUser')
                ->filter()
                ->unique('id');

            $campaignStats = [
                'total_members' => max(1, $totalMembers),
                'ordered_members' => $orderedMembersCount,
                'progress_percent' => min(100, round(($orderedMembersCount / max(1, $totalMembers)) * 100)),
                'total_pool_value' => $totalPoolValue,
                'participants' => $participants,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ];
        }

        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();
        $unreadCount = DB::table('user_notifications')->where('global_user_id', $user->id)->where('is_read', false)->count();

        return view('user.campaign', [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'activeCampaign' => $activeCampaign,
            'campaignStats' => $campaignStats,
            'categories' => $categories,
            'activeUserOrder' => $activeUserOrder,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ]);
    }

    /**
     * Handle the show operation.
     * @param Campaign $campaign Parameter value.
     * @param Request $request Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function show(Campaign $campaign, Request $request): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        abort_unless($campaign->room_id === $room->id && in_array($campaign->status?->value, ['active', 'scheduled'], true), 404);

        if ($request->expectsJson()) {
            return response()->json(['data' => $campaign->load(['items' => fn ($q) => $q->where('status', 'active')->with(['sizes', 'toppings']), 'room'])]);
        }

        return redirect()->route('user.campaigns.index', $room->slug);
    }
}
