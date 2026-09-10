<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = ['version', 'title', 'changelog', 'release_date', 'force_refresh', 'important', 'created_by_admin_id'];
    protected function casts(): array
    {
        return ['release_date' => 'date', 'force_refresh' => 'boolean', 'important' => 'boolean'];
    }
}
