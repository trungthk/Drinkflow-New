<?php

namespace App\Http\Controllers\User\Global;

use App\Enums\RoomUserStatus;
use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoomsController extends Controller
{
    /**
     * Display the list of rooms the global user has joined.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function __invoke(Request $request): View|JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Display or list rooms of the user.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        // Backward compatibility for JSON clients
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $user->roomUsers()->with('room')->where('status', 'active')->paginate(20),
            ]);
        }

        // Summary counts for filter tabs
        $baseQuery = $user->roomUsers();
        $totalCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('status', 'active')->count();
        $restrictedCount = (clone $baseQuery)->where('status', 'blocked')->count();

        // Search, filter, and sort parameters
        $search = trim((string) $request->get('q', ''));
        $filter = $request->get('filter', 'all');
        $sort = $request->get('sort', 'latest');

        $query = $user->roomUsers()
            ->with([
                'room' => function ($q) {
                    $q->withCount(['roomUsers' => function ($ru) {
                        $ru->where('status', 'active');
                    }]);
                },
                'orders',
            ]);

        // Filter by membership status
        if ($filter === 'active') {
            $query->where('status', 'active');
        } elseif ($filter === 'restricted' || $filter === 'blocked') {
            $query->where('status', 'blocked');
        }

        // Filter by search keyword (room name, slug, or numeric ID)
        if ($search !== '') {
            $query->whereHas('room', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        // Sorting
        if ($sort === 'oldest') {
            $query->orderBy('joined_at', 'asc')->orderBy('id', 'asc');
        } elseif ($sort === 'name_asc') {
            $query->join('rooms', 'room_users.room_id', '=', 'rooms.id')
                ->select('room_users.*')
                ->orderBy('rooms.name', 'asc');
        } elseif ($sort === 'spent_desc') {
            $query->select('room_users.*')
                ->selectSub(
                    Order::selectRaw('COALESCE(SUM(final_amount), 0)')
                        ->whereColumn('orders.room_user_id', 'room_users.id'),
                    'total_spent_sub'
                )
                ->orderByDesc('total_spent_sub');
        } else {
            // Default latest
            $query->orderByDesc('joined_at')->orderByDesc('id');
        }

        $paginator = $query->paginate(9)->withQueryString();

        // Map room users to display objects with formatted statistics
        $roomUsers = $paginator->through(function ($ru) {
            $orders = $ru->orders;
            $ordersCount = $orders->count();
            $totalSpent = (int) $orders->sum('final_amount');

            $latestOrder = $orders->sortByDesc('created_at')->first();
            $lastOrderTime = null;
            if ($latestOrder && $latestOrder->created_at) {
                $lastOrderTime = $latestOrder->created_at->diffForHumans();
            } elseif ($ru->last_active_at) {
                $lastOrderTime = $ru->last_active_at->diffForHumans();
            }

            $statusVal = $ru->status instanceof RoomUserStatus ? $ru->status->value : (string) $ru->status;
            $isActive = $statusVal === 'active';
            $isBlocked = $statusVal === 'blocked';

            $roomSlug = $ru->room?->slug ?: ('room-' . $ru->room_id);
            $roomIdDisplay = 'ROOM-ID: ' . strtoupper($roomSlug);

            return (object) [
                'id' => $ru->id,
                'room' => $ru->room,
                'room_id' => $ru->room_id,
                'room_name' => $ru->room?->name ?? 'Room #' . $ru->room_id,
                'room_id_display' => $roomIdDisplay,
                'status' => $statusVal,
                'is_active' => $isActive,
                'is_blocked' => $isBlocked,
                'joined_at_formatted' => $ru->joined_at ? $ru->joined_at->format('d/m/Y') : ($ru->created_at ? $ru->created_at->format('d/m/Y') : 'N/A'),
                'orders_count' => $ordersCount,
                'total_spent' => $totalSpent,
                'total_spent_formatted' => number_format($totalSpent, 0, ',', '.') . 'đ',
                'last_order_time' => $lastOrderTime,
                'dashboard_url' => $ru->room ? route('user.dashboard', $ru->room->slug ?? $ru->room->id) : '#',
            ];
        });

        // Notifications for layout
        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->latest()->take(5)->get();

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.rooms.breadcrumb_rooms'), 'url' => route('user.me.rooms')],
        ];

        return view('user.global.rooms', [
            'user' => $user,
            'roomUsers' => $roomUsers,
            'paginator' => $paginator,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'restrictedCount' => $restrictedCount,
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort,
            'breadcrumbs' => $breadcrumbs,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Handle fast room joining by room URL (or room code fallback).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function joinByCode(Request $request): RedirectResponse
    {
        $inputUrl = trim((string) ($request->input('room_url') ?? $request->input('room_code')));
        $request->merge(['room_url' => $inputUrl]);

        $request->validate([
            'room_url' => ['required', 'url', 'max:500'],
        ], [
            'room_url.required' => __('global.rooms.url_required'),
            'room_url.url' => __('global.rooms.url_invalid'),
        ]);

        $path = parse_url($inputUrl, PHP_URL_PATH) ?? '';

        if (!preg_match('#/rooms/([^/]+)#', $path, $matches)) {
            return back()->withInput()->withErrors(['room_url' => __('global.rooms.invalid_room_link')]);
        }

        $slugOrId = trim($matches[1]);
        $slugOrId = preg_replace('/^ROOM-ID:\s*/i', '', $slugOrId);
        $slugOrId = trim($slugOrId);

        $roomQuery = Room::query();
        if (is_numeric($slugOrId)) {
            $roomQuery->where('id', (int) $slugOrId)->orWhere('slug', $slugOrId);
        } else {
            $roomQuery->whereRaw('LOWER(slug) = ?', [strtolower($slugOrId)])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($slugOrId)]);
        }

        $room = $roomQuery->first();

        if (! $room) {
            return back()->withInput()->withErrors(['room_url' => __('global.rooms.room_not_found')]);
        }

        if ($room->status !== 'active') {
            return back()->withInput()->withErrors(['room_url' => __('global.rooms.room_inactive')]);
        }

        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');
        if ($user) {
            $membership = $user->roomUsers()->where('room_id', $room->id)->first();
            if ($membership) {
                $statusVal = $membership->status instanceof RoomUserStatus ? $membership->status->value : (string) $membership->status;
                if ($statusVal === 'active') {
                    return redirect()->route('user.dashboard', $room)->with('status', __('global.rooms.already_member', ['name' => $room->name]));
                } elseif ($statusVal === 'blocked') {
                    return back()->withInput()->withErrors(['room_url' => __('global.rooms.room_access_restricted')]);
                }
            }
        }

        return redirect()->route('user.rooms.join.show', $room);
    }
}
