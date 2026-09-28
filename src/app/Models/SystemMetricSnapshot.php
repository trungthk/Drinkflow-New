<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One infrastructure reading captured by drinkflow:capture-system-metrics.
 *
 * Nullable columns mean "could not be measured" (e.g. storage on a non-local disk, socket gateway not configured),
 * which the dashboard renders as gaps rather than zeros.
 */
class SystemMetricSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'captured_at', 'database_ok', 'pending_jobs', 'failed_jobs',
        'storage_used_bytes', 'storage_total_bytes', 'socket_ok', 'socket_connections',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'database_ok' => 'boolean',
            'pending_jobs' => 'integer',
            'failed_jobs' => 'integer',
            'storage_used_bytes' => 'integer',
            'storage_total_bytes' => 'integer',
            'socket_ok' => 'boolean',
            'socket_connections' => 'integer',
        ];
    }
}
