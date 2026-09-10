<?php

namespace App\Services\Notification;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;

class UserNotificationService
{
    /**
     * Handle the to room operation.
     * @param Room $room Parameter value.
     * @param string $type Parameter value.
     * @param string $title Parameter value.
     * @param ?string $body Parameter value.
     * @param array $data Parameter value.
     * @return void Result of the operation.
     */
    public function toRoom(Room $room, string $type, string $title, ?string $body = null, array $data = []): void
    {
        $room->roomUsers()->where('status', 'active')->each(function (RoomUser $roomUser) use ($type, $title, $body, $data): void {
            $this->toRoomUser($roomUser, $type, $title, $body, $data);
        });
    }

    /**
     * Handle the to room user operation.
     * @param RoomUser $roomUser Parameter value.
     * @param string $type Parameter value.
     * @param string $title Parameter value.
     * @param ?string $body Parameter value.
     * @param array $data Parameter value.
     * @return UserNotification Result of the operation.
     */
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
