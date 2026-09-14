<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['admin_id', 'room_id', 'audit_log_id', 'type', 'title', 'body', 'data', 'read_at'];

    /**
     * Define attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    /**
     * Get the recipient admin.
     *
     * @return BelongsTo<AdminAccount, $this>
     */
    public function admin(): BelongsTo { return $this->belongsTo(AdminAccount::class); }

    /**
     * Get the related room.
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }

    /**
     * Get the source audit log.
     *
     * @return BelongsTo<AuditLog, $this>
     */
    public function auditLog(): BelongsTo { return $this->belongsTo(AuditLog::class); }
}
