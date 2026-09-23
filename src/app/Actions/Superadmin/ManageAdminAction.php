<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\AdminRoomsUpdated;

class ManageAdminAction
{
    /**
     * Handle the create operation.
     * @param array $data Parameter value.
     * @return AdminAccount Result of the operation.
     */
    public function create(array $data): AdminAccount
    {
        return DB::transaction(function () use ($data): AdminAccount {
            $admin = AdminAccount::create($data);
            app(AuditService::class)->record('admin.created', 'admin', $admin->id, null, [], $admin->only(['name', 'email', 'role', 'status']));
            return $admin->fresh();
        });
    }

    /**
     * Handle the update operation.
     * @param AdminAccount $admin Parameter value.
     * @param array $data Parameter value.
     * @return AdminAccount Result of the operation.
     */
    public function update(AdminAccount $admin, array $data): AdminAccount
    {
        return DB::transaction(function () use ($admin, $data): AdminAccount {
            $this->guardRoleChange($admin, $data['role'] ?? null);
            $before = $admin->only(['name', 'email', 'role', 'status']);
            $admin->update($data);
            app(AuditService::class)->record('admin.updated', 'admin', $admin->id, null, $before, $admin->fresh()->only(array_keys($before)));
            return $admin->fresh();
        });
    }

    /**
     * Handle the set status operation.
     * @param AdminAccount $admin Parameter value.
     * @param string $status Parameter value.
     * @return AdminAccount Result of the operation.
     */
    public function setStatus(AdminAccount $admin, string $status): AdminAccount
    {
        return DB::transaction(function () use ($admin, $status): AdminAccount {
            if (AdminStatus::tryFrom($status) === null) throw ValidationException::withMessages(['status' => __('superadmin.actions.invalid_admin_status')]);
            if ($status !== AdminStatus::Active->value && $admin->isSuperadmin()) $this->ensureAnotherSuperadmin($admin);
            $before = ['status' => $admin->status];
            $admin->update(['status' => $status]);
            app(AuditService::class)->record('admin.status_updated', 'admin', $admin->id, null, $before, ['status' => $status]);
            return $admin->fresh();
        });
    }

    /**
     * Handle the set role operation.
     * @param AdminAccount $admin Parameter value.
     * @param string $role Parameter value.
     * @return AdminAccount Result of the operation.
     */
    public function setRole(AdminAccount $admin, string $role): AdminAccount
    {
        return DB::transaction(function () use ($admin, $role): AdminAccount {
            $this->guardRoleChange($admin, $role);
            $before = ['role' => $admin->role?->value];
            $admin->update(['role' => $role]);
            app(AuditService::class)->record('admin.role_updated', 'admin', $admin->id, null, $before, ['role' => $role]);
            return $admin->fresh();
        });
    }

    /**
     * Handle the reset password operation.
     * @param AdminAccount $admin Parameter value.
     * @param string $password Parameter value.
     * @return void Result of the operation.
     */
    public function resetPassword(AdminAccount $admin, string $password): void
    {
        $admin->update(['password' => $password]);
        app(AuditService::class)->record('admin.password_reset', 'admin', $admin->id);
    }

    /**
     * Handle the sync rooms operation.
     * @param AdminAccount $admin Parameter value.
     * @param array $roomIds Parameter value.
     * @return AdminAccount Result of the operation.
     */
    public function syncRooms(AdminAccount $admin, array $roomIds): AdminAccount
    {
        return DB::transaction(function () use ($admin, $roomIds): AdminAccount {
            $before = $admin->rooms()->pluck('rooms.id')->sort()->values()->all();
            $admin->rooms()->sync($roomIds);
            $after = $admin->rooms()->pluck('rooms.id')->sort()->values()->all();
            app(AuditService::class)->record('admin.rooms_updated', 'admin', $admin->id, null, ['room_ids' => $before], ['room_ids' => $after]);
            event(new AdminRoomsUpdated($admin->id, $after));
            return $admin->fresh('rooms');
        });
    }

    /**
     * Handle the delete operation.
     * @param AdminAccount $admin Parameter value.
     * @return void Result of the operation.
     */
    public function delete(AdminAccount $admin): void
    {
        DB::transaction(function () use ($admin): void {
            if ($admin->isSuperadmin()) $this->ensureAnotherSuperadmin($admin);
            app(AuditService::class)->record('admin.deleted', 'admin', $admin->id, null, $admin->only(['name', 'email', 'role', 'status']));
            $admin->delete();
        });
    }

    /**
     * Handle the guard role change operation.
     * @param AdminAccount $admin Parameter value.
     * @param ?string $role Parameter value.
     * @return void Result of the operation.
     */
    private function guardRoleChange(AdminAccount $admin, ?string $role): void
    {
        if ($role !== AdminRole::Admin->value || ! $admin->isSuperadmin()) return;
        $current = request()->user('admin');
        if ($current?->is($admin) && $this->superadminCount() <= 1) {
            throw ValidationException::withMessages(['role' => __('superadmin.actions.cannot_demote_last_superadmin')]);
        }
        $this->ensureAnotherSuperadmin($admin);
    }

    /**
     * Handle the ensure another superadmin operation.
     * @param AdminAccount $admin Parameter value.
     * @return void Result of the operation.
     */
    private function ensureAnotherSuperadmin(AdminAccount $admin): void
    {
        $query = AdminAccount::query()->where('role', AdminRole::SuperAdmin->value)->where('status', AdminStatus::Active)->where('id', '<>', $admin->id);
        if (! $query->exists()) throw ValidationException::withMessages(['admin' => __('superadmin.actions.must_retain_at_least_one_superadmin')]);
    }

    /**
     * Handle the superadmin count operation.
     * @return int Result of the operation.
     */
    private function superadminCount(): int
    {
        return AdminAccount::query()->where('role', AdminRole::SuperAdmin->value)->count();
    }
}
