<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationChannel extends Model
{
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = ['room_id', 'type', 'name', 'config_encrypted', 'status'];
    protected $hidden = ['config_encrypted'];
    protected function configEncrypted(): Attribute
    {
        return Attribute::make(get: fn($value) => $value, set: fn($value) => encrypt($value));
    }
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
