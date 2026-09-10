<?php

namespace App\Actions\User;

use App\Models\GlobalUser;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetGlobalUserStatusAction
{
    /**
     * Handle the execute operation.
     * @param GlobalUser $user Parameter value.
     * @param string $status Parameter value.
     * @return GlobalUser Result of the operation.
     */
    public function execute(GlobalUser $user, string $status): GlobalUser
    {
        if (!in_array($status, ['active', 'blocked', 'disabled'], true)) throw ValidationException::withMessages(['status' => 'Tráº¡ng thĂ¡i khĂ´ng há»£p lá»‡.']);
        return DB::transaction(function () use ($user, $status) {
            $user->refresh();
            $before = $user->status->value;
            $user->update(['status' => $status]);
            app(AuditService::class)->record('global_user.status_updated', 'global_user', $user->id, null, ['status' => $before], ['status' => $status]);
            return $user->fresh();
        });
    }
}
