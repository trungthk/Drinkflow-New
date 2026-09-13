<?php

namespace App\Actions\User;

use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use Illuminate\Support\Facades\DB;

class JoinRoomAction
{
    /**
     * Handle the execute operation.
     * @param GlobalUser $globalUser Parameter value.
     * @param Room $room Parameter value.
     * @param string $deviceUuid Parameter value.
     * @param string $deviceTokenHash Parameter value.
     * @return RoomUser Result of the operation.
     */
    public function execute(GlobalUser $globalUser, Room $room, string $deviceUuid, string $deviceTokenHash): RoomUser
    {
        $globalUser->refresh();
        $room->refresh();
        $userStatus = $globalUser->status instanceof \BackedEnum ? $globalUser->status->value : (string) ($globalUser->status ?? 'active');
        abort_unless($userStatus === 'active', 403);
        abort_unless($room->status === 'active', 404);

        return DB::transaction(function () use ($globalUser, $room, $deviceUuid, $deviceTokenHash): RoomUser {
            $roomUser = RoomUser::query()->firstOrCreate(
                ['room_id' => $room->id, 'global_user_id' => $globalUser->id],
                [
                    'user_code' => $this->uniqueCode($room, $globalUser->normalized_name),
                    'display_name' => $globalUser->name,
                    'normalized_name' => $globalUser->normalized_name,
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            );

            $roomUserStatus = $roomUser->status instanceof \BackedEnum ? $roomUser->status->value : (string) $roomUser->status;
            abort_unless($roomUserStatus === 'active', 403);
            RoomUserDevice::updateOrCreate(
                ['room_user_id' => $roomUser->id, 'device_uuid' => $deviceUuid],
                ['token_hash' => $deviceTokenHash, 'verified_at' => now(), 'last_seen_at' => now(), 'revoked_at' => null],
            );
            $roomUser->update(['last_active_at' => now()]);
            return $roomUser->fresh();
        });
    }

    /**
     * Handle the unique code operation.
     * @param Room $room Parameter value.
     * @param string $normalizedName Parameter value.
     * @return string Result of the operation.
     */
    private function uniqueCode(Room $room, string $normalizedName): string
    {
        $base = preg_replace('/[^A-Z0-9]/', '', strtoupper($normalizedName)) ?: 'USER';
        $code = $base;
        $suffix = 1;
        while ($room->roomUsers()->where('user_code', $code)->exists()) { $code = $base . (++$suffix); }
        return $code;
    }
}
