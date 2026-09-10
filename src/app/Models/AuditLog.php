<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model { public $timestamps = false; protected $fillable = ['actor_type','actor_id','event','target_type','target_id','room_id','ip_address','user_agent','device_uuid','before_data','after_data','metadata','created_at']; protected function casts(): array { return ['before_data'=>'array','after_data'=>'array','metadata'=>'array','created_at'=>'datetime']; } }
