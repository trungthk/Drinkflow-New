<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform notification addressed to one Superadmin account.
 *
 * One row per recipient: a platform alert (for example a new Agent registration waiting for review)
 * is written for every active Superadmin, and each account reads and clears its own copy. Mirrors
 * {@see AdminNotification}, which carries the room-scoped notifications of Agents.
 *
 * @property int $id
 * @property int $superadmin_id
 * @property string $type
 * @property string $title
 * @property string|null $body
 * @property array<string, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $read_at
 */
class SuperadminNotification extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['superadmin_id', 'type', 'title', 'body', 'data', 'read_at'];

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
     * Recipient Superadmin account.
     *
     * @return BelongsTo<Superadmin, $this> Recipient.
     */
    public function superadmin(): BelongsTo
    {
        return $this->belongsTo(Superadmin::class);
    }
}
