<?php
namespace App\Actions\User;
use App\Models\RoomUser; use App\Services\Audit\AuditService; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class SetRoomUserStatusAction { public function execute(RoomUser $roomUser,string $status): RoomUser { if(!in_array($status,['active','blocked','removed'],true)) throw ValidationException::withMessages(['status'=>'Trạng thái không hợp lệ.']); return DB::transaction(function()use($roomUser,$status){$before=$roomUser->status->value;$roomUser->update(['status'=>$status]);app(AuditService::class)->record('room_user.status_updated','room_user',$roomUser->id,$roomUser->room_id,['status'=>$before],['status'=>$status]);return $roomUser->fresh();}); } }
