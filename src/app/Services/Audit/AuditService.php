<?php
namespace App\Services\Audit;
use App\Models\AuditLog; use Illuminate\Http\Request;
class AuditService { public function record(string $event, string $targetType, int $targetId, ?int $roomId=null, array $before=[], array $after=[], array $metadata=[]): AuditLog { $request=app(Request::class); $actor=$request->user('admin') ?? $request->user('web'); return AuditLog::create(['actor_type'=>$actor ? ($request->user('admin') ? 'admin':'user') : 'system','actor_id'=>$actor?->id,'event'=>$event,'target_type'=>$targetType,'target_id'=>$targetId,'room_id'=>$roomId,'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),'before_data'=>$before,'after_data'=>$after,'metadata'=>$metadata,'created_at'=>now()]); } }
