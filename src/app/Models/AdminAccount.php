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

    protected $fillable = ['name', 'email', 'password', 'role', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'role'          => AdminRole::class,
            'status'        => AdminStatus::class,
            'last_login_at' => 'datetime',
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
     * Kiểm tra tài khoản admin có phải là Superadmin không.
     *
     * @return bool True nếu là superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }
}
