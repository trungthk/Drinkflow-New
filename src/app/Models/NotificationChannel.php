<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
class NotificationChannel extends Model { protected $fillable = ['room_id','type','name','config_encrypted','status']; protected $hidden = ['config_encrypted']; protected function configEncrypted(): Attribute { return Attribute::make(get: fn ($value) => $value, set: fn ($value) => encrypt($value)); } }
