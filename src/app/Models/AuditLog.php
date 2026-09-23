<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuditLog extends Model
{
    /** actor_type values written by App\Services\Audit\AuditService::record(). */
    public const ACTOR_SUPERADMIN = 'superadmin';
    public const ACTOR_ADMIN = 'admin';
    public const ACTOR_USER = 'user';
    public const ACTOR_SYSTEM = 'system';

    /** Actor types performed through the admin console (room admins and superadmins). */
    public const ADMIN_ACTOR_TYPES = [self::ACTOR_ADMIN, self::ACTOR_SUPERADMIN];

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
    /**
     * Admin account that performed the action; only meaningful when actor_type is admin/superadmin.
     *
     * @return BelongsTo<AdminAccount, $this>
     */
    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminAccount::class, 'actor_id');
    }

    /**
     * Human-readable, translated label of the event (admin.audit_event_*), falling back to the raw key.
     *
     * @param string $event Event key such as "campaign.force_closed".
     * @return string Translated label.
     */
    public static function labelForEvent(string $event): string
    {
        $key = 'admin.audit_event_'.str_replace('.', '_', $event);
        $label = __($key);

        return $label === $key ? $event : $label;
    }

    /**
     * Translated label of this log's event.
     *
     * @return string Translated label or the raw event key when no translation exists.
     */
    public function eventLabel(): string
    {
        return self::labelForEvent((string) $this->event);
    }

    /**
     * Translated label of this log's target type (admin.audit_target_*), falling back to the raw type.
     *
     * @return string Translated label.
     */
    public function targetLabel(): string
    {
        $key = 'admin.audit_target_'.$this->target_type;
        $label = __($key);

        return $label === $key ? (string) $this->target_type : $label;
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(AdminAccount::class, 'admin_audit_logs', 'audit_log_id', 'admin_id');
    }
}
