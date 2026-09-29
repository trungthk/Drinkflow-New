<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminStatus;
use App\Enums\Permission;
use App\Models\Concerns\HasStatus;
use App\Services\Authorization\AgentScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Authenticatable
{
    /**
     * Agents a Superadmin may see: `all` → every Agent, `managed` → assigned Agents only.
     *
     * Use it for lists and for aggregates (counts, sums) over Agents alike, so totals never
     * include Agents outside the Superadmin's scope.
     *
     * @param Builder<Admin> $query Agent query.
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised (`agent.view` by default).
     * @return Builder<Admin> Constrained query.
     * @throws AuthorizationException (403) When the Superadmin does not hold the permission.
     */
    public function scopeVisibleTo(Builder $query, Superadmin $superadmin, Permission $permission = Permission::AgentView): Builder
    {
        if (! $superadmin->hasPermission($permission)) {
            throw new AuthorizationException();
        }

        return app(AgentScope::class)->apply($query, $superadmin, $permission, $query->qualifyColumn('id'));
    }

    use Notifiable, HasStatus;

    protected $table = 'admins';

    protected $fillable = ['name', 'email', 'password', 'status', 'last_login_at', 'avatar_url', 'phone', 'department', 'two_factor_enabled'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'status'        => AdminStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'phone'         => 'encrypted',
        ];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'admin_rooms', 'admin_id', 'room_id');
    }

    /**
     * Superadmins this Agent is assigned to (at most one of them is primary).
     *
     * @return BelongsToMany<Superadmin, $this> Managing Superadmins with the assignment data on the pivot.
     */
    public function superadmins(): BelongsToMany
    {
        return $this->belongsToMany(Superadmin::class, 'superadmin_admins', 'admin_id', 'superadmin_id')
            ->using(SuperadminAdmin::class)
            ->withPivot(SuperadminAdmin::PIVOT_COLUMNS)
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id')->where('actor_type', 'admin');
    }

    /**
     * Get activity logs linked to this admin through the audit pivot table.
     *
     * @return BelongsToMany<AuditLog, $this>
     */
    public function linkedAuditLogs(): BelongsToMany
    {
        return $this->belongsToMany(AuditLog::class, 'admin_audit_logs', 'admin_id', 'audit_log_id');
    }

    /**
     * Get notifications addressed to this admin.
     *
     * @return HasMany<AdminNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class, 'admin_id');
    }

}
