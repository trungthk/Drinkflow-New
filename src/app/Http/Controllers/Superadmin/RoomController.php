<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ManageRoomAction;
use App\Enums\RoomUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetStatusRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Enums\Permission;
use App\Models\Room;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private readonly AgentScope $scope) {}

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
        $this->scope->applyToRooms($query, $this->superadmin($request), Permission::RoomView);
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
        Gate::authorize('create', Room::class);
        $data = $request->validated();
        $adminIds = $data['admin_ids'] ?? [];
        $ownerId = $data['owner_admin_id'] ?? null;
        unset($data['admin_ids'], $data['owner_admin_id']);
        $this->assertAdminsInScope($request, $ownerId !== null ? [...$adminIds, $ownerId] : $adminIds);

        $room = $action->create($data);
        if ($ownerId !== null) {
            $room = app(\App\Services\Room\RoomOwnershipService::class)->assign($room, \App\Models\Admin::query()->findOrFail((int) $ownerId));
        }
        if ($adminIds !== []) {
            $room = $action->syncAdmins($room, $adminIds);
        }
        return response()->json(['data' => $room->load('admins:id,name,email,status')], 201);
    }

    /**
     * Handle the show operation.
     * @param Room $room Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Room $room): JsonResponse
    {
        Gate::authorize('view', $room);
        return response()->json(['data' => $room->load(['admins:id,name,email,status', 'roomUsers.globalUser', 'campaigns'])->loadCount(['roomUsers', 'campaigns', 'admins'])]);
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
        Gate::authorize('update', $room);
        $data = $request->validated();
        $adminIds = $data['admin_ids'] ?? null;
        $ownerId = $data['owner_admin_id'] ?? null;
        unset($data['admin_ids'], $data['owner_admin_id']);
        $this->assertAdminsInScope($request, $ownerId !== null ? [...($adminIds ?? []), $ownerId] : ($adminIds ?? []));

        $result = $action->update($room, $data);
        if ($ownerId !== null) {
            $result = app(\App\Services\Room\RoomOwnershipService::class)->assign($result, \App\Models\Admin::query()->findOrFail((int) $ownerId));
        }
        if ($adminIds !== null) {
            $result = $action->syncAdmins($result, $adminIds);
        }
        return response()->json(['data' => $result->load('admins:id,name,email,status')]);
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
        Gate::authorize('update', $room);
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
        Gate::authorize('delete', $room);
        $action->delete($room);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * Signed-in superadmin (the route middleware guarantees one).
     *
     * @param Request $request Incoming request.
     * @return Superadmin Superadmin.
     */
    private function superadmin(Request $request): Superadmin
    {
        /** @var Superadmin $superadmin */
        $superadmin = $request->user('superadmin');

        return $superadmin;
    }

    /**
     * Refuse assigning Agents outside the superadmin's room-management scope.
     *
     * @param Request $request Incoming request.
     * @param array<int, int|string> $adminIds Requested Agent IDs.
     * @return void
     * @throws ValidationException When an Agent is outside the scope.
     */
    private function assertAdminsInScope(Request $request, array $adminIds): void
    {
        if (! $this->scope->allowsAll($this->superadmin($request), Permission::RoomManage, $adminIds)) {
            throw ValidationException::withMessages(['admin_ids' => __('superadmin.actions.admins_out_of_scope')]);
        }
    }
}
