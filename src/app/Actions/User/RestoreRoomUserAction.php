<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\RoomUserStatus;
use App\Models\RoomUser;
use Illuminate\Validation\ValidationException;

class RestoreRoomUserAction
{
    /**
     * Create the action instance.
     *
     * @param SetRoomUserStatusAction $setStatus Membership status action (audit trail + realtime event).
     */
    public function __construct(private readonly SetRoomUserStatusAction $setStatus)
    {
    }

    /**
     * Restore a previously removed member back to the active state.
     *
     * Devices revoked when the member was removed stay revoked, so the member has to sign in again.
     *
     * @param RoomUser $roomUser Removed membership to restore.
     * @return RoomUser Restored membership.
     * @throws ValidationException When the membership is not in the removed state.
     */
    public function execute(RoomUser $roomUser): RoomUser
    {
        if ($roomUser->status !== RoomUserStatus::Removed) {
            throw ValidationException::withMessages([
                'room_user' => __('admin.cannot_restore_member_not_removed'),
            ]);
        }

        return $this->setStatus->execute($roomUser, RoomUserStatus::Active->value);
    }
}
