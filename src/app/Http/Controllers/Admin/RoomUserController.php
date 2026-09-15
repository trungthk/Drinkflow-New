<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\User\SetRoomUserStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetStatusRequest;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use App\Services\Auth\DeviceTrustService;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomUserController extends Controller
{
    /**
     * Handle the index operation.
     */
    public function index(Request $request, Room $room): JsonResponse
    {
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

    /**
     * Display the standalone Room Users & Device Trust directory view.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @param \App\Services\Admin\AdminRoomUserService $userService Room user service.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room, \App\Services\Admin\AdminRoomUserService $userService): View
    {
        $query = $room->roomUsers()
            ->with(['globalUser', 'devices'])
            ->withCount(['devices', 'orders', 'debts'])
            ->latest();

        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);
            $query->where(function ($userQuery) use ($normalizedSearch): void {
                $userQuery->whereRaw('LOWER(user_code) LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereRaw('LOWER(display_name) LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereHas('globalUser', function ($globalQuery) use ($normalizedSearch): void {
                        $globalQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $normalizedSearch . '%']);
                    });
            });
        }

        $status = $request->string('status')->toString();
        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $roomUsers = $query->paginate(50)->withQueryString();

        $metrics = $userService->getDirectoryMetrics($room);

        return view('admin.users', array_merge([
            'room' => $room,
            'roomUsers' => $roomUsers,
            'filters' => ['q' => $search, 'status' => $status !== '' ? $status : 'all'],
        ], $metrics));
    }

    /**
     * Handle the show operation.
     */
    public function show(Room $room, RoomUser $roomUser, \App\Services\Admin\AdminRoomUserService $userService): JsonResponse
    {
        $this->assertRoom($room, $roomUser);
        return response()->json(['data' => $userService->formatUserDetail($roomUser)]);
    }

    /**
     * Handle the status operation.
     */
    public function status(SetStatusRequest $request, Room $room, RoomUser $roomUser, SetRoomUserStatusAction $action): JsonResponse
    {
        $this->assertRoom($room, $roomUser);
        return response()->json(['data' => $action->execute($roomUser, $request->validated('status'))]);
    }

    /**
     * Soft-remove a user from the room while preserving membership history.
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Membership to remove.
     * @param SetRoomUserStatusAction $action Membership status action.
     * @return JsonResponse Removal result.
     */
    public function destroy(Room $room, RoomUser $roomUser, SetRoomUserStatusAction $action): JsonResponse
    {
        $this->assertRoom($room, $roomUser);

        return response()->json(['data' => $action->execute($roomUser, \App\Enums\RoomUserStatus::Removed->value)]);
    }

    /**
     * Handle the revoke device operation.
     */
    public function revokeDevice(Room $room, RoomUser $roomUser, RoomUserDevice $device, DeviceTrustService $trust, AuditService $audit): JsonResponse
    {
        $this->assertRoom($room, $roomUser);
        abort_unless($device->room_user_id === $roomUser->id, 404);
        $trust->revoke($device);
        $audit->record('room_user.device_revoked', 'room_user_device', $device->id, $roomUser->room_id, [], ['revoked_at' => $device->fresh()->revoked_at]);
        return response()->json(['data' => ['revoked' => true]]);
    }

    /**
     * Assert that the room user belongs to the current room.
     */
    private function assertRoom(Room $room, RoomUser $roomUser): void
    {
        abort_unless($roomUser->room_id === $room->id, 404);
    }
}
