<?php

namespace App\Http\Controllers\Admin;

use App\Actions\User\SetRoomUserStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetStatusRequest;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use App\Services\Auth\DeviceTrustService;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = $room->roomUsers()->with('globalUser')->withCount(['devices', 'orders', 'debts'])->latest();
        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            $query->where(function ($query) use ($term): void {
                $query->where('user_code', 'like', "%{$term}%")->orWhere('display_name', 'like', "%{$term}%")->orWhereHas('globalUser', fn ($global) => $global->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            });
        }
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        return response()->json(['data' => $query->paginate(50)]);
    }

    public function show(RoomUser $roomUser): JsonResponse
    {
        $this->assertRoom($roomUser);
        $roomUser->load(['globalUser', 'orders.items.toppings', 'debts.campaign', 'devices']);
        $roomUser->setRelation('devices', $roomUser->devices->map(fn (RoomUserDevice $device) => ['id' => $device->id, 'device_uuid' => substr($device->device_uuid, 0, 8).'…', 'verified_at' => $device->verified_at, 'last_seen_at' => $device->last_seen_at, 'revoked_at' => $device->revoked_at, 'status' => $device->revoked_at ? 'revoked' : 'active']));
        return response()->json(['data' => $roomUser]);
    }

    public function status(SetStatusRequest $request, RoomUser $roomUser, SetRoomUserStatusAction $action): JsonResponse
    {
        $this->assertRoom($roomUser);
        return response()->json(['data' => $action->execute($roomUser, $request->validated('status'))]);
    }

    public function revokeDevice(RoomUser $roomUser, RoomUserDevice $device, DeviceTrustService $trust, AuditService $audit): JsonResponse
    {
        $this->assertRoom($roomUser);
        abort_unless($device->room_user_id === $roomUser->id, 404);
        $trust->revoke($device);
        $audit->record('room_user.device_revoked', 'room_user_device', $device->id, $roomUser->room_id, null, ['revoked_at' => $device->fresh()->revoked_at]);
        return response()->json(['data' => ['revoked' => true]]);
    }

    private function assertRoom(RoomUser $roomUser): void
    {
        abort_unless($roomUser->room_id === request()->attributes->get('room')->id, 404);
    }
}
