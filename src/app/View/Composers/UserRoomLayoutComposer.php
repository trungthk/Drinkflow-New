<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Constants\AppLocale;
use App\Enums\CampaignStatus;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\Notification\NotificationPresentationService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserRoomLayoutComposer
{
    public function __construct(private readonly NotificationPresentationService $presentation)
    {
    }
    /**
     * Share user, room, active campaign, unread notifications and locale data with the room layout.
     *
     * @param View $view Blade view instance.
     * @return void
     */
    public function compose(View $view): void
    {
        $data = $view->getData();
        $user = $data['user'] ?? request()->attributes->get('global_user') ?? auth('web')->user();
        $room = $data['room'] ?? request()->attributes->get('room') ?? request()->route('room');
        $roomUser = $data['roomUser'] ?? request()->attributes->get('room_user');
        $notifications = $data['notifications'] ?? null;
        $unreadCount = $data['unreadNotificationsCount'] ?? null;

        if ($user instanceof GlobalUser) {
            if ($notifications === null) {
                $notifications = $user->notifications()
                    ->whereNull('read_at')
                    ->latest('created_at')
                    ->take(5)
                    ->get();
            }
            if ($unreadCount === null) {
                $unreadCount = $user->notifications()
                    ->whereNull('read_at')
                    ->count();
            }
        } else {
            $notifications = $notifications ?? collect();
            $unreadCount = $unreadCount ?? 0;
        }

        $activeCampaign = $data['activeCampaign'] ?? null;
        if ($activeCampaign === null && $room instanceof Room) {
            $campaign = $room->campaigns()
                ->where('status', CampaignStatus::Active->value)
                ->latest('started_at')
                ->first();

            $activeCampaign = $campaign ? [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'time_remaining' => $campaign->deadline
                    ? ($campaign->deadline->isFuture() ? $campaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00')
                    : '14:22',
            ] : null;
        }

        $userRooms = $data['userRooms'] ?? collect();
        if ((!$userRooms instanceof Collection || $userRooms->isEmpty()) && $user instanceof GlobalUser) {
            $userRooms = $user->roomUsers()
                ->with('room')
                ->get()
                ->pluck('room')
                ->filter()
                ->values();
        }

        $currentLocale = app()->getLocale();

        $view->with([
            'user' => $user,
            'room' => $room,
            'roomUser' => $roomUser,
            'notifications' => $notifications,
            'unreadNotificationsCount' => (int) ($unreadCount ?? 0),
            'activeCampaign' => $activeCampaign,
            'userRooms' => $userRooms,
            'currentLocale' => $currentLocale,
            'locales' => AppLocale::SUPPORTED,
            'activeLocaleMeta' => AppLocale::get($currentLocale),
            'notificationPresentations' => $this->presentNotifications($notifications),
        ]);
    }

    /**
     * Prepare localized notification content for the room header dropdown.
     *
     * @param Collection<int, mixed> $notifications Notifications shown in the header.
     * @return array<int|string, array{title: string, body: string, icon: string}> Presentation data by notification ID.
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
