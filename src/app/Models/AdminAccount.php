<?php

namespace App\Models;

use App\Enums\AdminRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminAccount extends Authenticatable
{
    use Notifiable;
    protected $table = 'admin_accounts';
    protected $fillable = ['name', 'email', 'password', 'role', 'status'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['password' => 'hashed', 'role' => AdminRole::class, 'last_login_at' => 'datetime'];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'admin_rooms', 'admin_id', 'room_id');
    }

    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id')->where('actor_type', 'admin');
    }

    public function isSuperadmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
