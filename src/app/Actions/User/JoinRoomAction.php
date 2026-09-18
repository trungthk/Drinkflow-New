<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use App\Services\Code\CodeGeneratorService;
use Illuminate\Support\Facades\DB;

class JoinRoomAction
{
    /**
     * Thực hiện gia nhập phòng cho người dùng.
     *
     * @param  GlobalUser  $globalUser  Người dùng toàn hệ thống.
     * @param  Room        $room        Phòng cần gia nhập.
     * @param  string      $deviceUuid  UUID thiết bị hiện tại.
     * @param  string      $deviceTokenHash  Hash token trusted device.
     * @return RoomUser Bản ghi RoomUser sau khi gia nhập.
     */
    public function execute(GlobalUser $globalUser, Room $room, string $deviceUuid, string $deviceTokenHash): RoomUser
    {
        $globalUser->refresh();
        $room->refresh();

        abort_unless($globalUser->status === GlobalUserStatus::Active, 403);
        abort_unless($room->status === RoomStatus::Active, 404);

        return DB::transaction(function () use ($globalUser, $room, $deviceUuid, $deviceTokenHash): RoomUser {
            $roomUser = RoomUser::query()->firstOrCreate(
                ['room_id' => $room->id, 'global_user_id' => $globalUser->id],
                [
                    'user_code'      => CodeGeneratorService::generateRoomUserCode(),
                    'display_name'   => $globalUser->name,
                    'normalized_name' => $globalUser->normalized_name,
                    'status'         => $room->roomSettings()->where('key', 'is_public')->value('value') === '1'
                        ? RoomUserStatus::Blocked
                        : RoomUserStatus::Active,
                    'joined_at'      => now(),
                ],
            );

            if ($roomUser->status === RoomUserStatus::Blocked && ! $roomUser->wasRecentlyCreated) {
                abort(403);
            }

            if ($roomUser->status === RoomUserStatus::Removed) {
                $roomUser->update([
                    'status' => $room->roomSettings()->where('key', 'is_public')->value('value') === '1'
                        ? RoomUserStatus::Blocked
                        : RoomUserStatus::Active,
                    'joined_at' => now(),
                    'last_active_at' => now(),
                ]);
            }

            RoomUserDevice::updateOrCreate(
                ['room_user_id' => $roomUser->id, 'device_uuid' => $deviceUuid],
                ['token_hash' => $deviceTokenHash, 'verified_at' => now(), 'last_seen_at' => now(), 'revoked_at' => null],
            );
            $roomUser->update(['last_active_at' => now()]);

            return $roomUser->fresh();
        });
    }
}
