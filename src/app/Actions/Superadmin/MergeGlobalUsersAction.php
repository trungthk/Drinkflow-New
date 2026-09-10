<?php

namespace App\Actions\Superadmin;

use App\Models\GlobalUser;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeGlobalUsersAction
{
    public function execute(GlobalUser $source, GlobalUser $target): GlobalUser
    {
        if ($source->is($target)) throw ValidationException::withMessages(['target_id' => 'Không thể gộp tài khoản với chính nó.']);

        return DB::transaction(function () use ($source, $target): GlobalUser {
            $source->load('oauthIdentities', 'roomUsers');
            $target->load('oauthIdentities', 'roomUsers');
            $targetRooms = $target->roomUsers->pluck('room_id')->all();
            if ($source->roomUsers->pluck('room_id')->intersect($targetRooms)->isNotEmpty()) {
                throw ValidationException::withMessages(['source_id' => 'Hai tài khoản cùng có membership trong một room; cần xử lý membership trước.']);
            }
            $targetIdentityKeys = $target->oauthIdentities->map(fn ($identity) => $identity->provider.':'.$identity->provider_user_id)->all();
            foreach ($source->oauthIdentities as $identity) {
                if (in_array($identity->provider.':'.$identity->provider_user_id, $targetIdentityKeys, true)) {
                    throw ValidationException::withMessages(['source_id' => 'Hai tài khoản có OAuth identity trùng nhau.']);
                }
                $identity->update(['global_user_id' => $target->id]);
            }
            $source->roomUsers()->update(['global_user_id' => $target->id]);
            $before = ['source_id' => $source->id, 'source_email' => $source->email];
            $source->delete();
            app(AuditService::class)->record('global_user.merged', 'global_user', $target->id, null, $before, ['target_id' => $target->id]);
            return $target->fresh(['oauthIdentities', 'roomUsers']);
        });
    }
}
