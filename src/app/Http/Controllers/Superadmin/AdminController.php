<?php

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
    public function index(Request $request): JsonResponse
    {
        $query = AdminAccount::with('rooms:id,name,slug')->withCount('rooms')->latest();
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%'));
        if ($request->filled('role')) $query->where('role', $request->string('role')->toString());
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(StoreAdminRequest $request, ManageAdminAction $action): JsonResponse
    {
        $data = $request->validated(); $roomIds = $data['room_ids'] ?? []; unset($data['room_ids']);
        $admin = $action->create($data);
        if ($roomIds !== []) $admin = $action->syncRooms($admin, $roomIds);
        return response()->json(['data' => $admin->load('rooms')], 201);
    }

    public function show(AdminAccount $admin): JsonResponse { return response()->json(['data' => $admin->load('rooms:id,name,slug')]); }

    public function update(UpdateAdminRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse
    {
        $data = $request->validated(); $roomIds = $data['room_ids'] ?? null; unset($data['room_ids']);
        if (array_key_exists('password', $data) && $data['password'] === null) unset($data['password']);
        $admin = $action->update($admin, $data);
        if ($roomIds !== null) $admin = $action->syncRooms($admin, $roomIds);
        return response()->json(['data' => $admin->load('rooms')]);
    }

    public function status(SetStatusRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse { return response()->json(['data' => $action->setStatus($admin, $request->validated('status'))]); }
    public function role(AdminRoleRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse { return response()->json(['data' => $action->setRole($admin, $request->validated('role'))]); }
    public function rooms(AdminRoomsRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse { return response()->json(['data' => $action->syncRooms($admin, $request->validated('room_ids'))]); }
    public function resetPassword(AdminPasswordRequest $request, AdminAccount $admin, ManageAdminAction $action): JsonResponse { $action->resetPassword($admin, $request->validated('password')); return response()->json(['data' => ['reset' => true]]); }
    public function destroy(AdminAccount $admin, ManageAdminAction $action): JsonResponse { $action->delete($admin); return response()->json(['data' => ['deleted' => true]]); }
}
