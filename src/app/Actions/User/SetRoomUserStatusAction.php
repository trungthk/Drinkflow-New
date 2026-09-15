<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\RoomUserStatus;
use App\Events\RoomMembershipUpdated;
use App\Models\RoomUser;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetRoomUserStatusAction
{
    /**
     * Change room user member status with audit trail logging.
     *
     * @param RoomUser $roomUser Room user entity instance.
     * @param string $status New status string ('active', 'blocked', 'removed').
     * @return RoomUser Updated room user instance.
     * @throws ValidationException If status string is invalid.
     */
    public function execute(RoomUser $roomUser, string $status): RoomUser
    {
        if (RoomUserStatus::tryFrom($status) === null) {
            throw ValidationException::withMessages([
                'status' => __('admin.invalid_status'),
            ]);
        }

        $updatedRoomUser = DB::transaction(function () use ($roomUser, $status): RoomUser {
            $before = $roomUser->status->value;
            $roomUser->update(['status' => $status]);

            if ($status === RoomUserStatus::Removed->value) {
                $roomUser->devices()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }

            app(AuditService::class)->record('room_user.status_updated', 'room_user', $roomUser->id, $roomUser->room_id, ['status' => $before], ['status' => $status]);

            return $roomUser->fresh();
        });

        RoomMembershipUpdated::dispatch($updatedRoomUser);

        return $updatedRoomUser;
    }
}
