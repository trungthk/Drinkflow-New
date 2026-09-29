<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ManageAdminAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPasswordRequest;
use App\Http\Requests\AdminRoomsRequest;
use App\Http\Requests\SetStatusRequest;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Room;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private readonly AgentScope $scope) {}

    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Admin::with('rooms:id,name,slug')->withCount('rooms')->latest()->visibleTo($this->superadmin($request));
        if ($request->filled('q'))
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->string('q') . '%')->orWhere('email', 'like', '%' . $request->string('q') . '%'));
        if ($request->filled('status'))
            $query->where('status', $request->string('status')->toString());
        return response()->json(['data' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)]);
    }

    /**
     * Handle the store operation.
     * @param StoreAdminRequest $request Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(StoreAdminRequest $request, ManageAdminAction $action): JsonResponse
    {
        Gate::authorize('create', Admin::class);
        $data = $request->validated();
        $roomIds = $data['room_ids'] ?? [];
        unset($data['room_ids']);
        $this->assertRoomsManageable($request, $roomIds);
        $admin = $action->create($data);
        if ($roomIds !== [])
            $admin = $action->syncRooms($admin, $roomIds);
        return response()->json(['data' => $admin->load('rooms')], 201);
    }

    /**
     * Handle the show operation.
     * @param Admin $admin Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Admin $admin): JsonResponse
    {
        Gate::authorize('view', $admin);
        return response()->json(['data' => $admin->load('rooms:id,name,slug')]);
    }

    /**
     * Handle the update operation.
     * @param UpdateAdminRequest $request Parameter value.
     * @param Admin $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(UpdateAdminRequest $request, Admin $admin, ManageAdminAction $action): JsonResponse
    {
        Gate::authorize('update', $admin);
        $data = $request->validated();
        $roomIds = $data['room_ids'] ?? null;
        unset($data['room_ids']);
        $this->assertRoomsManageable($request, $roomIds ?? []);
        if (array_key_exists('password', $data) && $data['password'] === null)
            unset($data['password']);
        $admin = $action->update($admin, $data);
        if ($roomIds !== null)
            $admin = $action->syncRooms($admin, $roomIds);
        return response()->json(['data' => $admin->load('rooms')]);
    }

    /**
     * Handle the status operation.
     * @param SetStatusRequest $request Parameter value.
     * @param Admin $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function status(SetStatusRequest $request, Admin $admin, ManageAdminAction $action): JsonResponse
    {
        Gate::authorize('update', $admin);
        $result = $action->setStatus($admin, $request->validated('status'));
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the rooms operation.
     * @param AdminRoomsRequest $request Parameter value.
     * @param Admin $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function rooms(AdminRoomsRequest $request, Admin $admin, ManageAdminAction $action): JsonResponse
    {
        Gate::authorize('update', $admin);
        $roomIds = $request->validated('room_ids');
        $this->assertRoomsManageable($request, $roomIds);
        $result = $action->syncRooms($admin, $roomIds);
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the reset password operation.
     * @param AdminPasswordRequest $request Parameter value.
     * @param Admin $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function resetPassword(AdminPasswordRequest $request, Admin $admin, ManageAdminAction $action): JsonResponse
    {
        Gate::authorize('update', $admin);
        $action->resetPassword($admin, $request->validated('password'));
        return response()->json(['data' => ['reset' => true]]);
    }

    /**
     * Handle the destroy operation.
     * @param Admin $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(Admin $admin, ManageAdminAction $action): JsonResponse
    {
        Gate::authorize('delete', $admin);
        $action->delete($admin);
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
     * Refuse room assignments outside the rooms the superadmin may manage.
     *
     * @param Request $request Incoming request.
     * @param array<int, int|string> $roomIds Requested room IDs.
     * @return void
     * @throws ValidationException When a room is outside the superadmin's scope.
     */
    private function assertRoomsManageable(Request $request, array $roomIds): void
    {
        if ($roomIds === []) {
            return;
        }
        $visible = $this->scope->applyToRooms(Room::query(), $this->superadmin($request), Permission::RoomManage)
            ->whereIn('id', $roomIds)->count();
        if ($visible !== count(array_unique(array_map('intval', $roomIds)))) {
            throw ValidationException::withMessages(['room_ids' => __('superadmin.actions.rooms_out_of_scope')]);
        }
    }
}
