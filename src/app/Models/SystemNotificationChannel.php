<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SystemNotificationChannel extends Model
{
    protected $fillable = ['type', 'name', 'config_encrypted', 'status'];
    protected $hidden = ['config_encrypted'];
    protected $appends = ['configured'];

    protected function configEncrypted(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => encrypt($value),
        );
    }

    protected function configured(): Attribute
    {
        return Attribute::get(fn () => filled($this->config_encrypted));
    }
}
