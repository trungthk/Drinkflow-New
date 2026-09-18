<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON = 'json';

    protected $fillable = ['key', 'value', 'type', 'is_secret', 'updated_by_admin_id'];
    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }
}
