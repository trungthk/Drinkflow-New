<?php

namespace App\Services\Notification;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;

class UserNotificationService
{
    public function toRoom(Room $room, string $type, string $title, ?string $body = null, array $data = []): void
    {
        $room->roomUsers()->where('status', 'active')->each(function (RoomUser $roomUser) use ($type, $title, $body, $data): void {
            $this->toRoomUser($roomUser, $type, $title, $body, $data);
        });
    }

    public function toRoomUser(RoomUser $roomUser, string $type, string $title, ?string $body = null, array $data = []): UserNotification
    {
        return UserNotification::create([
            'global_user_id' => $roomUser->global_user_id,
            'room_user_id' => $roomUser->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
