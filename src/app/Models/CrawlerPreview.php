<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrawlerPreview extends Model
{
    protected $table = 'crawler_previews';

    protected $fillable = ['room_id', 'admin_id', 'source_url', 'items', 'expires_at'];

    protected function casts(): array
    {
        return ['items' => 'array', 'expires_at' => 'datetime'];
    }
}
