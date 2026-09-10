<?php

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ManageRoomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetStatusRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::query()->withCount(['roomUsers', 'campaigns', 'admins', 'roomUsers as active_room_users_count' => fn ($q) => $q->where('status', 'active'), 'roomUsers as blocked_room_users_count' => fn ($q) => $q->where('status', 'blocked')])->latest();
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('slug', 'like', '%'.$request->string('q').'%'));
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(StoreRoomRequest $request, ManageRoomAction $action): JsonResponse { return response()->json(['data' => $action->create($request->validated())], 201); }

    public function show(Room $room): JsonResponse
    {
        return response()->json(['data' => $room->load(['admins:id,name,email,role,status', 'roomUsers.globalUser', 'campaigns'])->loadCount(['roomUsers', 'campaigns', 'admins'])]);
    }

    public function update(UpdateRoomRequest $request, Room $room, ManageRoomAction $action): JsonResponse { return response()->json(['data' => $action->update($room, $request->validated())]); }

    public function status(SetStatusRequest $request, Room $room, ManageRoomAction $action): JsonResponse
    {
        abort_unless(in_array($request->validated('status'), ['active', 'disabled', 'archived'], true), 422);
        return response()->json(['data' => $action->setStatus($room, $request->validated('status'))]);
    }
}
