<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Enums\CampaignItemStatus;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRoomCampaignService
{
    /**
     * Tìm kiếm và phân trang danh sách chiến dịch đang diễn ra hoặc đã lên lịch trong phòng.
     *
     * @param  \App\Models\Room     $room    Đối tượng phòng chứa chiến dịch
     * @param  \Illuminate\Http\Request  $request Đối tượng HTTP Request chứa từ khóa tìm kiếm q hoặc danh mục category
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator  Phân trang chiến dịch kèm món ăn và topping
     */
    public function searchCampaigns(Room $room, Request $request): LengthAwarePaginator
    {
        $query = Campaign::query()
            ->where('room_id', $room->id)
            ->whereIn('status', [CampaignStatus::Active->value, CampaignStatus::Scheduled->value]);

        return $query->with(['items' => function ($q) use ($request) {
            $q->where('status', CampaignItemStatus::Active)
              ->when($request->filled('q'), fn ($items) => $items->where(function ($search) use ($request) {
                  $term = trim($request->string('q')->toString());
                  $search->where('name', 'like', "%{$term}%")
                         ->orWhere('normalized_name', 'like', '%' . strtoupper(Str::ascii($term)) . '%');
              }))
              ->when($request->filled('category'), fn ($items) => $items->where('category', $request->string('category')))
              ->with(['sizes', 'toppings']);
        }])
        ->when($request->filled('q'), fn ($campaigns) => $campaigns->whereHas('items', fn ($items) => $items
            ->where('status', CampaignItemStatus::Active)
            ->where(function ($search) use ($request) {
                $term = trim($request->string('q')->toString());
                $search->where('name', 'like', "%{$term}%")
                       ->orWhere('normalized_name', 'like', '%' . strtoupper(Str::ascii($term)) . '%');
            })
        ))
        ->orderBy('deadline')
        ->paginate(20);
    }

    /**
     * Thu thập toàn bộ dữ liệu hiển thị cho trang chiến dịch của phòng (menu món, tiến độ, thành viên đặt món).
     *
     * @param  \App\Models\Room          $room     Đối tượng phòng hiện tại
     * @param  \App\Models\RoomUser|null $roomUser Thành viên phòng của người dùng hiện tại
     * @param  \App\Models\GlobalUser|null $user   Tài khoản người dùng toàn hệ thống
     * @return array<string, mixed>  Mảng dữ liệu View chiến dịch
     */
    public function getCampaignViewData(Room $room, ?RoomUser $roomUser, ?GlobalUser $user): array
    {
        $activeCampaign = $room->campaigns()
            ->where('status', CampaignStatus::Active)
            ->with(['items' => function ($q) {
                $q->where('status', CampaignItemStatus::Active)->with(['sizes', 'toppings']);
            }])
            ->first();

        $categories     = collect();
        $activeUserOrder = null;
        $campaignStats  = null;

        /** @var array<int, \App\Enums\OrderStatus> $activeOrderStatuses */
        $activeOrderStatuses = [
            OrderStatus::Submitted->value,
            OrderStatus::Confirmed->value,
            OrderStatus::Ordering->value,
            OrderStatus::Ordered->value,
            OrderStatus::Delivering->value,
        ];

        if ($activeCampaign) {
            $categories      = $activeCampaign->items->pluck('category')->filter()->unique()->values();
            $activeUserOrder = $roomUser
                ? $roomUser->orders()
                    ->where('campaign_id', $activeCampaign->id)
                    ->whereIn('status', $activeOrderStatuses)
                    ->with('items.toppings')
                    ->latest()
                    ->first()
                : null;

            $totalMembers       = $room->roomUsers()->where('status', RoomUserStatus::Active)->count();
            $orderedMembersCount = Order::where('campaign_id', $activeCampaign->id)->distinct('room_user_id')->count('room_user_id');
            $totalPoolValue     = Order::where('campaign_id', $activeCampaign->id)->sum('final_amount');
            $participants       = Order::where('campaign_id', $activeCampaign->id)
                ->with('roomUser.globalUser')
                ->latest()
                ->take(10)
                ->get()
                ->pluck('roomUser')
                ->filter()
                ->unique('id');

            $campaignStats = [
                'total_members'     => max(1, $totalMembers),
                'ordered_members'   => $orderedMembersCount,
                'progress_percent'  => min(100, round(($orderedMembersCount / max(1, $totalMembers)) * 100)),
                'total_pool_value'  => $totalPoolValue,
                'participants'      => $participants,
                'time_remaining'    => $activeCampaign->deadline
                    ? ($activeCampaign->deadline->isFuture()
                        ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true])
                        : '00:00')
                    : '14:22',
            ];
        }

        $userRooms  = $user ? $user->rooms()->where('rooms.status', RoomStatus::Active)->get() : collect();
        $unreadCount = $user ? DB::table('user_notifications')->where('global_user_id', $user->id)->whereNull('read_at')->count() : 0;

        return [
            'room'                       => $room,
            'roomUser'                   => $roomUser,
            'user'                       => $user,
            'activeCampaign'             => $activeCampaign,
            'campaignStats'              => $campaignStats,
            'categories'                 => $categories,
            'activeUserOrder'            => $activeUserOrder,
            'userRooms'                  => $userRooms,
            'unreadNotificationsCount'   => $unreadCount,
        ];
    }
}
