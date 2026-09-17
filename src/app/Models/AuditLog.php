<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['actor_type', 'actor_id', 'event', 'target_type', 'target_id', 'room_id', 'ip_address', 'user_agent', 'device_uuid', 'before_data', 'after_data', 'metadata', 'created_at'];
    protected function casts(): array
    {
        return ['before_data' => 'array', 'after_data' => 'array', 'metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function room(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get admin accounts linked to this activity record.
     *
     * @return BelongsToMany<AdminAccount, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(AdminAccount::class, 'admin_audit_logs', 'audit_log_id', 'admin_id');
    }
}
