<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\GlobalUserStatus;
use App\Models\GlobalUser;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetGlobalUserStatusAction
{
    /**
     * Change global user account status with audit logging.
     *
     * @param GlobalUser $user Global user entity instance.
     * @param string $status New status ('active', 'blocked', 'disabled').
     * @return GlobalUser Updated global user instance.
     * @throws ValidationException If status string is invalid.
     */
    public function execute(GlobalUser $user, string $status): GlobalUser
    {
        if (GlobalUserStatus::tryFrom($status) === null) {
            throw ValidationException::withMessages([
                'status' => __('admin.invalid_status'),
            ]);
        }

        return DB::transaction(function () use ($user, $status): GlobalUser {
            $user->refresh();
            $before = $user->status->value;
            $user->update(['status' => $status]);
            app(AuditService::class)->record('global_user.status_updated', 'global_user', $user->id, null, ['status' => $before], ['status' => $status]);

            return $user->fresh();
        });
    }
}

