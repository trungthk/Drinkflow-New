<?php

namespace App\Actions\Superadmin;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\AdminRoomsUpdated;

class ManageAdminAction
{
    public function create(array $data): AdminAccount
    {
        return DB::transaction(function () use ($data): AdminAccount {
            $admin = AdminAccount::create($data);
            app(AuditService::class)->record('admin.created', 'admin', $admin->id, null, [], $admin->only(['name', 'email', 'role', 'status']));
            return $admin->fresh();
        });
    }

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

    public function setStatus(AdminAccount $admin, string $status): AdminAccount
    {
        return DB::transaction(function () use ($admin, $status): AdminAccount {
            if (! in_array($status, ['active', 'blocked', 'disabled'], true)) throw ValidationException::withMessages(['status' => 'Trạng thái admin không hợp lệ.']);
            if ($status !== 'active' && $admin->isSuperadmin()) $this->ensureAnotherSuperadmin($admin);
            $before = ['status' => $admin->status];
            $admin->update(['status' => $status]);
            app(AuditService::class)->record('admin.status_updated', 'admin', $admin->id, null, $before, ['status' => $status]);
            return $admin->fresh();
        });
    }

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

    public function resetPassword(AdminAccount $admin, string $password): void
    {
        $admin->update(['password' => $password]);
        app(AuditService::class)->record('admin.password_reset', 'admin', $admin->id);
    }

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

    public function delete(AdminAccount $admin): void
    {
        DB::transaction(function () use ($admin): void {
            if ($admin->isSuperadmin()) $this->ensureAnotherSuperadmin($admin);
            app(AuditService::class)->record('admin.deleted', 'admin', $admin->id, null, $admin->only(['name', 'email', 'role', 'status']));
            $admin->delete();
        });
    }

    private function guardRoleChange(AdminAccount $admin, ?string $role): void
    {
        if ($role !== AdminRole::Admin->value || ! $admin->isSuperadmin()) return;
        $current = request()->user('admin');
        if ($current?->is($admin) && $this->superadminCount() <= 1) {
            throw ValidationException::withMessages(['role' => 'Không thể tự hạ quyền superadmin cuối cùng.']);
        }
        $this->ensureAnotherSuperadmin($admin);
    }

    private function ensureAnotherSuperadmin(AdminAccount $admin): void
    {
        $query = AdminAccount::query()->where('role', AdminRole::SuperAdmin->value)->where('status', 'active')->where('id', '<>', $admin->id);
        if (! $query->exists()) throw ValidationException::withMessages(['admin' => 'Phải giữ lại ít nhất một superadmin.']);
    }

    private function superadminCount(): int
    {
        return AdminAccount::query()->where('role', AdminRole::SuperAdmin->value)->count();
    }
}
