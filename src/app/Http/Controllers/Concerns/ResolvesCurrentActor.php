<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Http\Request;

/**
 * Trait hỗ trợ truy xuất nhanh đối tượng User/Admin/Room hiện tại từ HTTP Request.
 */
trait ResolvesCurrentActor
{
    /**
     * Lấy đối tượng GlobalUser hiện tại từ Request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\GlobalUser|null
     */
    protected function currentGlobalUser(Request $request): ?GlobalUser
    {
        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        return $user instanceof GlobalUser ? $user : null;
    }

    /**
     * Lấy đối tượng Admin hiện tại từ Request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\AdminAccount|null
     */
    protected function currentAdmin(Request $request): ?AdminAccount
    {
        /** @var AdminAccount|null $admin */
        $admin = $request->user('admin');

        return $admin instanceof AdminAccount ? $admin : null;
    }

    /**
     * Lấy đối tượng Room hiện tại từ Route hoặc Attributes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Room|null
     */
    protected function currentRoom(Request $request): ?Room
    {
        $room = $request->attributes->get('room') ?? $request->route('room');

        if ($room instanceof Room) {
            return $room;
        }

        if (is_numeric($room)) {
            return Room::query()->where('id', (int) $room)->first();
        }

        if (is_string($room) && $room !== '') {
            return Room::query()->where('slug', $room)->orWhere('id', is_numeric($room) ? (int) $room : 0)->first();
        }

        return null;
    }

    /**
     * Lấy đối tượng RoomUser (thành viên phòng) hiện tại.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room|null  $room
     * @return \App\Models\RoomUser|null
     */
    protected function currentRoomUser(Request $request, ?Room $room = null): ?RoomUser
    {
        $roomUser = $request->attributes->get('room_user');
        if ($roomUser instanceof RoomUser) {
            return $roomUser;
        }

        $user = $this->currentGlobalUser($request);
        $targetRoom = $room ?? $this->currentRoom($request);

        if ($user && $targetRoom) {
            return $user->roomUsers()->where('room_id', $targetRoom->id)->first();
        }

        return null;
    }
}
