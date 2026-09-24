<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ManageRoomAction;
use App\Enums\RoomUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetStatusRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Room::query()->withCount([
            'roomUsers',
            'campaigns',
            'admins',
            'roomUsers as active_room_users_count' => fn($q) => $q->where('status', RoomUserStatus::Active),
            'roomUsers as blocked_room_users_count' => fn($q) => $q->where('status', RoomUserStatus::Blocked),
        ])->latest();
        if ($request->filled('q'))
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->string('q') . '%')->orWhere('slug', 'like', '%' . $request->string('q') . '%'));
        if ($request->filled('status'))
            $query->where('status', $request->string('status')->toString());
        return response()->json(['data' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)]);
    }

    /**
     * Handle the store operation.
     * @param StoreRoomRequest $request Parameter value.
     * @param ManageRoomAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(StoreRoomRequest $request, ManageRoomAction $action): JsonResponse
    {
        $data = $request->validated();
        $adminIds = $data['admin_ids'] ?? [];
        unset($data['admin_ids']);

        $room = $action->create($data);
        if ($adminIds !== []) {
            $room = $action->syncAdmins($room, $adminIds);
        }
        return response()->json(['data' => $room->load('admins:id,name,email,role,status')], 201);
    }

    /**
     * Handle the show operation.
     * @param Room $room Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Room $room): JsonResponse
    {
        return response()->json(['data' => $room->load(['admins:id,name,email,role,status', 'roomUsers.globalUser', 'campaigns'])->loadCount(['roomUsers', 'campaigns', 'admins'])]);
    }

    /**
     * Handle the update operation.
     * @param UpdateRoomRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param ManageRoomAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(UpdateRoomRequest $request, Room $room, ManageRoomAction $action): JsonResponse
    {
        $data = $request->validated();
        $adminIds = $data['admin_ids'] ?? null;
        unset($data['admin_ids']);

        $result = $action->update($room, $data);
        if ($adminIds !== null) {
            $result = $action->syncAdmins($result, $adminIds);
        }
        return response()->json(['data' => $result->load('admins:id,name,email,role,status')]);
    }

    /**
     * Handle the status operation.
     * @param SetStatusRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param ManageRoomAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function status(SetStatusRequest $request, Room $room, ManageRoomAction $action): JsonResponse
    {
        abort_unless(\App\Enums\RoomStatus::tryFrom((string) $request->validated('status')) !== null, 422);
        $result = $action->setStatus($room, $request->validated('status'));
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the destroy operation.
     *
     * @param Room $room Room to delete.
     * @param ManageRoomAction $action Room management action.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(Room $room, ManageRoomAction $action): JsonResponse
    {
        $action->delete($room);

        return response()->json(['data' => ['deleted' => true]]);
    }
}
