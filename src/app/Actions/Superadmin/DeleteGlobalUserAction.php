<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\GlobalUserStatus;
use App\Enums\RoomUserStatus;
use App\Events\ForceReloadRequested;
use App\Events\RoomMembershipUpdated;
use App\Models\GlobalUser;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Soft-delete a global user account from the superadmin console.
 *
 * Orders and debts reference room_users with restrictOnDelete, so a hard delete would fail (or
 * lose accounting history). Instead the account is marked "deleted", which every auth path
 * already rejects (only "active" users may sign in), and each room membership is removed with
 * its trusted devices revoked. Order and debt history stays intact for reports.
 */
class DeleteGlobalUserAction
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * @param GlobalUser $user Account to delete.
     * @return GlobalUser The account in its "deleted" state.
     *
     * @throws ValidationException When the user still owes money or is already deleted.
     */
    public function execute(GlobalUser $user): GlobalUser
    {
        if ($user->status === GlobalUserStatus::Deleted) {
            throw ValidationException::withMessages([
                'global_user' => __('superadmin.users.already_deleted'),
            ]);
        }

        if ($user->hasOutstandingDebts()) {
            throw ValidationException::withMessages([
                'global_user' => __('admin.cannot_delete_user_with_outstanding_debt'),
            ]);
        }

        /** @var array<int, RoomUser> $removedMemberships */
        $removedMemberships = [];

        $deleted = DB::transaction(function () use ($user, &$removedMemberships): GlobalUser {
            $user = GlobalUser::query()->lockForUpdate()->findOrFail($user->id);
            $before = ['status' => $user->status?->value];
            $now = now();

            $user->roomUsers()
                ->where('status', '!=', RoomUserStatus::Removed->value)
                ->get()
                ->each(function (RoomUser $roomUser) use (&$removedMemberships): void {
                    $roomUser->update(['status' => RoomUserStatus::Removed]);
                    $removedMemberships[] = $roomUser;
                });

            RoomUserDevice::query()
                ->whereIn('room_user_id', $user->roomUsers()->select('id'))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $now]);

            $user->update(['status' => GlobalUserStatus::Deleted]);
            $this->audit->record('global_user.deleted', 'global_user', $user->id, null, $before, ['status' => GlobalUserStatus::Deleted->value]);

            return $user->fresh();
        });

        foreach ($removedMemberships as $roomUser) {
            RoomMembershipUpdated::dispatch($roomUser->fresh());
        }
        // Pages the deleted user still has open reload and land on the sign-in flow right away.
        event(ForceReloadRequested::forGlobalUser($deleted->id, ForceReloadRequested::REASON_ACCOUNT_DELETED));

        return $deleted;
    }
}
