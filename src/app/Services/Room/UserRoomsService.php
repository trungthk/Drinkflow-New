<?php

declare(strict_types=1);

namespace App\Services\Room;

use App\Enums\RoomUserStatus;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Http\Request;

class UserRoomsService
{
    /**
     * Thu thập và phân trang danh sách các phòng mà người dùng đã tham gia kèm bộ lọc và tìm kiếm.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa các query params: search, filter, sort
     * @return array<string, mixed>  Mảng dữ liệu cho View quản lý phòng
     */
    public function getRoomsData(GlobalUser $user, Request $request): array
    {
        // Summary counts for filter tabs
        $baseQuery = $user->roomUsers();
        $totalCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('status', RoomUserStatus::Active->value)->count();
        $restrictedCount = (clone $baseQuery)->where('status', RoomUserStatus::Blocked->value)->count();

        // Search, filter, and sort parameters
        $search = trim((string) $request->get('q', ''));
        $filter = $request->get('filter', 'all');
        $sort = $request->get('sort', 'latest');

        $query = $user->roomUsers()
            ->with([
                'room' => function ($q) {
                    $q->withCount(['roomUsers' => function ($ru) {
                        $ru->where('status', RoomUserStatus::Active->value);
                    }]);
                },
                'orders',
            ]);

        // Filter by membership status
        if ($filter === 'active') {
            $query->where('status', RoomUserStatus::Active->value);
        } elseif ($filter === 'restricted' || $filter === 'blocked') {
            $query->where('status', RoomUserStatus::Blocked->value);
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

        return [
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
        ];
    }

    /**
     * Tìm đối tượng phòng từ URL liên kết hoặc mã ID/slug nhập vào.
     *
     * @param  string  $inputUrl  Đường dẫn URL tham gia phòng (ví dụ: http://localhost:8080/rooms/tech-room)
     * @return \App\Models\Room|null  Đối tượng Room tìm được hoặc null nếu không hợp lệ
     */
    public function resolveRoomFromUrl(string $inputUrl): ?Room
    {
        $path = parse_url($inputUrl, PHP_URL_PATH) ?? '';

        if (!preg_match('#/rooms/([^/]+)#', $path, $matches)) {
            return null;
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

        return $roomQuery->first();
    }
}
