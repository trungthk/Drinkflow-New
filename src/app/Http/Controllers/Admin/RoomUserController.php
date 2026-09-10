<?php
namespace App\Http\Controllers\Admin;
use App\Actions\User\SetRoomUserStatusAction; use App\Http\Controllers\Controller; use App\Http\Requests\SetStatusRequest; use App\Models\RoomUser; use Illuminate\Http\JsonResponse;
class RoomUserController extends Controller { public function index(): JsonResponse { $room=request()->attributes->get('room'); return response()->json(['data'=>$room->roomUsers()->with('globalUser')->latest()->paginate(50)]); } public function status(SetStatusRequest $request,RoomUser $roomUser,SetRoomUserStatusAction $action): JsonResponse { abort_unless($roomUser->room_id===request()->attributes->get('room')->id,404); return response()->json(['data'=>$action->execute($roomUser,$request->validated('status'))]); } }
