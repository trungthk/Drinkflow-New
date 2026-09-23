<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Constants\AppLocale;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Models\Room;
use App\Models\Order;
use App\Services\Notification\AdminNotificationService;
use App\Services\Notification\NotificationPresentationService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminLayoutComposer
{
    /**
     * Create a composer for shared admin layout data.
     *
     * @param AdminNotificationService $notifications Service for unread admin notifications.
     * @param NotificationPresentationService $presentation Service for localized notification text.
     */
    public function __construct(
        private readonly AdminNotificationService $notifications,
        private readonly NotificationPresentationService $presentation,
    ) {
    }

    /**
     * Share the active room, live campaign and notification data with admin layouts.
     *
     * @param View $view Admin layout view instance.
     * @return void
     */
    public function compose(View $view): void
    {
        $data = $view->getData();
        $room = $data['room'] ?? request()->attributes->get('room') ?? request()->route('room');
        $adminUser = auth('admin')->user();
        $adminUser = $adminUser instanceof AdminAccount ? $adminUser : null;
        $assignedRooms = $data['assignedRooms'] ?? null;
        $assignedRoomsList = $this->assignedRooms($adminUser, $assignedRooms, $room);
        $unreadNotifications = $room instanceof Room && $adminUser instanceof AdminAccount
            ? $this->notifications->unreadForRoom($adminUser, $room)
            : collect();

        $view->with([
            'room' => $room,
            'adminUser' => $adminUser,
            'assignedRoomsList' => $assignedRoomsList,
            'hasLiveCampaign' => $room instanceof Room && $room->campaigns()
                ->where('status', CampaignStatus::Active->value)
                ->exists(),
            'realtimeOrderCount' => $room instanceof Room
                ? Order::query()->where('room_id', $room->id)->inLiveCampaign()
                    ->where('status', '!=', OrderStatus::Cancelled->value)
                    ->whereNull('cancelled_at')
                    ->count()
                : 0,
            'blockedUsersCount' => $room instanceof Room
                ? $room->roomUsers()->where('status', RoomUserStatus::Blocked->value)->count()
                : 0,
            'unreadNotifications' => $unreadNotifications,
            'unreadCount' => $unreadNotifications->count(),
            'notificationPresentations' => $this->presentNotifications($unreadNotifications),
            'currentLocale' => app()->getLocale(),
            'locales' => AppLocale::SUPPORTED,
            'activeLocaleMeta' => AppLocale::get(app()->getLocale()),
        ]);
    }

    /**
     * Resolve rooms available in the admin workspace selector.
     *
     * @param ?AdminAccount $admin Current admin account.
     * @param mixed $assignedRooms Rooms explicitly supplied by the caller.
     * @param mixed $room Active room.
     * @return Collection<int, Room> Available rooms.
     */
    private function assignedRooms(?AdminAccount $admin, mixed $assignedRooms, mixed $room): Collection
    {
        $rooms = $assignedRooms instanceof Collection
            ? $assignedRooms
            : ($admin?->isSuperadmin()
                ? Room::where('status', RoomStatus::Active)->orderBy('name')->get()
                : ($admin?->rooms()->where('status', RoomStatus::Active)->orderBy('name')->get() ?? collect()));

        if ($room instanceof Room && !$rooms->contains('id', $room->id)) {
            $rooms->prepend($room);
        }

        return $rooms;
    }

    /**
     * Build presentation data for each unread notification.
     *
     * @param Collection<int, mixed> $notifications Unread notifications.
     * @return array<int|string, array{title: string, body: string, icon: string, link: ?string}> Presentation keyed by notification id.
     */
    private function presentNotifications(Collection $notifications): array
    {
        $presentations = [];
        foreach ($notifications as $notification) {
            $presentations[$notification->getKey()] = $this->presentation->present($notification);
        }

        return $presentations;
    }
}
