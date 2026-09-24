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
    public function store(StoreAdminRequest $request, ManageAdminAction $action): JsonResponse
    {
        $data = $request->validated();
        $roomIds = $data['room_ids'] ?? [];
        unset($data['room_ids']);
        $admin = $action->create($data);
        if ($roomIds !== [])
            $admin = $action->syncRooms($admin, $roomIds);
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
    public function update(UpdateAdminRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $data = $request->validated();
        $roomIds = $data['room_ids'] ?? null;
        unset($data['room_ids']);
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
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function status(SetStatusRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $result = $action->setStatus($admin, $request->validated('status'));
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the role operation.
     * @param AdminRoleRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function role(AdminRoleRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $result = $action->setRole($admin, $request->validated('role'));
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the rooms operation.
     * @param AdminRoomsRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function rooms(AdminRoomsRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $roomIds = $request->validated('room_ids');
        $result = $action->syncRooms($admin, $roomIds);
        return response()->json(['data' => $result]);
    }

    /**
     * Handle the reset password operation.
     * @param AdminPasswordRequest $request Parameter value.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function resetPassword(AdminPasswordRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $action->resetPassword($admin, $request->validated('password'));
        return response()->json(['data' => ['reset' => true]]);
    }

    /**
     * Handle the destroy operation.
     * @param AdminAccount $admin Parameter value.
     * @param ManageAdminAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $action->delete($admin);
        return response()->json(['data' => ['deleted' => true]]);
    }
}
