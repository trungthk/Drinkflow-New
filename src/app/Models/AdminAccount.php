<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Models\Concerns\HasStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminAccount extends Authenticatable
{
    use Notifiable, HasStatus;

    protected $table = 'admin_accounts';

    protected $fillable = ['name', 'email', 'password', 'role', 'status', 'last_login_at', 'avatar_url', 'phone', 'department', 'two_factor_enabled'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'role'          => AdminRole::class,
            'status'        => AdminStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'admin_rooms', 'admin_id', 'room_id');
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

    /**
     * Kiểm tra tài khoản admin có phải là Superadmin không.
     *
     * @return bool True nếu là superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }
}
