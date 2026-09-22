<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ManageAdminAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPasswordRequest;
use App\Http\Requests\AdminRoleRequest;
use App\Http\Requests\AdminRoomsRequest;
use App\Http\Requests\SetStatusRequest;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdminAccount::with('rooms:id,name,slug')->withCount('rooms')->latest();
        if ($request->filled('q'))
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->string('q') . '%')->orWhere('email', 'like', '%' . $request->string('q') . '%'));
        if ($request->filled('role'))
            $query->where('role', $request->string('role')->toString());
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
    public function store(StoreAdminRequest $request, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $data = $request->validated();
        $roomIds = $data['room_ids'] ?? [];
        unset($data['room_ids']);
        $admin = $action->create($data);
        if ($roomIds !== [])
            $admin = $action->syncRooms($admin, $roomIds);
        $audit->record('admin.created', 'admin', $admin->id, null, [], $admin->only(['name', 'email', 'role', 'status']), ['room_ids' => $roomIds]);
        return response()->json(['data' => $admin->load('rooms')], 201);
    }

    /**
     * Handle the show operation.
     * @param AdminAccount $admin Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(AdminAccount $admin): JsonResponse
    {
        return response()->json(['data' => $admin->load('rooms:id,name,slug')]);
    }

    /**
     * Handle the update operation.
     * @param UpdateAdminRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(UpdateAdminRequest $request, AdminAccount $admin, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $before = $admin->only(['name', 'email', 'role', 'status']);
        $data = $request->validated();
        $roomIds = $data['room_ids'] ?? null;
        unset($data['room_ids']);
        if (array_key_exists('password', $data) && $data['password'] === null)
            unset($data['password']);
        $admin = $action->update($admin, $data);
        if ($roomIds !== null)
            $admin = $action->syncRooms($admin, $roomIds);
        $audit->record('admin.updated', 'admin', $admin->id, null, $before, $admin->fresh()->only(array_keys($before)), ['room_ids' => $roomIds]);
        return response()->json(['data' => $admin->load('rooms')]);
    }

    /**
     * Handle the status operation.
     * @param SetStatusRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function status(SetStatusRequest $request, AdminAccount $admin, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $before = ['status' => $admin->status?->value ?? (string) $admin->status];
        $result = $action->setStatus($admin, $request->validated('status'));
        $audit->record('admin.status_changed', 'admin', $admin->id, null, $before, ['status' => $request->validated('status')]);
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the role operation.
     * @param AdminRoleRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function role(AdminRoleRequest $request, AdminAccount $admin, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $before = ['role' => $admin->role instanceof \BackedEnum ? $admin->role->value : (string) $admin->role];
        $result = $action->setRole($admin, $request->validated('role'));
        $audit->record('admin.role_changed', 'admin', $admin->id, null, $before, ['role' => $request->validated('role')]);
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the rooms operation.
     * @param AdminRoomsRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function rooms(AdminRoomsRequest $request, AdminAccount $admin, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $roomIds = $request->validated('room_ids');
        $result = $action->syncRooms($admin, $roomIds);
        $audit->record('admin.rooms_synced', 'admin', $admin->id, null, [], [], ['room_ids' => $roomIds]);
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the reset password operation.
     * @param AdminPasswordRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function resetPassword(AdminPasswordRequest $request, AdminAccount $admin, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $action->resetPassword($admin, $request->validated('password'));
        $audit->record('admin.password_reset', 'admin', $admin->id, null, [], [], ['password_reset' => true]);
        return response()->json(['data' => ['reset' => true]]);
    }

    /**
     * Handle the destroy operation.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(AdminAccount $admin, ManageAdminAction $action, AuditService $audit): JsonResponse
    {
        $before = $admin->only(['name', 'email', 'role', 'status']);
        $action->delete($admin);
        $audit->record('admin.deleted', 'admin', $admin->id, null, $before, []);
        return response()->json(['data' => ['deleted' => true]]);
    }
}
