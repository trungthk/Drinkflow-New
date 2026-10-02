<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Room\ManageAgentRoomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAgentRoomRequest;
use App\Models\Admin;
use App\Models\Room;
use App\Services\Room\RoomQuotaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The Agent's own rooms (/admin/rooms): list with quota usage, create, edit, archive, restore.
 */
class RoomController extends Controller
{
    public function __construct(
        private readonly ManageAgentRoomAction $action,
        private readonly RoomQuotaService $quota,
    ) {}

    /**
     * Owned rooms (every status) and rooms shared with the Agent as collaborator.
     *
     * @param Request $request Incoming request.
     * @return View Room list.
     */
    public function index(Request $request): View
    {
        $admin = $this->admin($request);

        return view('admin.my-rooms.index', [
            'ownedRooms' => $admin->ownedRooms()->withCount('roomUsers')->orderByRaw("CASE WHEN status = 'archived' THEN 1 ELSE 0 END")->orderBy('name')->get(),
            'sharedRooms' => $admin->rooms()->where(static fn ($query) => $query->whereNull('owner_admin_id')->orWhere('owner_admin_id', '!=', $admin->id))->orderBy('name')->get(),
            'usage' => $this->quota->usage($admin),
        ]);
    }

    /**
     * Create form; back to the room list with the reason when the quota is used up.
     *
     * @param Request $request Incoming request.
     * @return View|RedirectResponse Form, or the room list when no room can be added.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $admin = $this->admin($request);
        if (! $this->quota->canAdd($admin)) {
            $usage = $this->quota->usage($admin);

            return redirect()->route('admin.rooms.index')->withErrors(['room' => $usage['has_subscription']
                ? __('platform.rooms.quota_reached', ['limit' => $usage['limit']])
                : __('platform.rooms.no_subscription')]);
        }

        return view('admin.my-rooms.form', ['room' => null, 'usage' => $this->quota->usage($admin)]);
    }

    /**
     * Create a room; refused server-side when the quota is used up.
     *
     * @param SaveAgentRoomRequest $request Validated room data.
     * @return RedirectResponse Room list.
     */
    public function store(SaveAgentRoomRequest $request): RedirectResponse
    {
        $room = $this->action->create($this->admin($request), $request->validated());

        return redirect()->route('admin.rooms.index')->with('status', __('platform.rooms.created', ['name' => $room->name]));
    }

    /**
     * Edit form of an owned room.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room.
     * @return View Form.
     */
    public function edit(Request $request, Room $room): View
    {
        Gate::forUser($this->admin($request))->authorize('manageAsOwner', $room);

        return view('admin.my-rooms.form', ['room' => $room, 'usage' => $this->quota->usage($this->admin($request))]);
    }

    /**
     * Update an owned room.
     *
     * @param SaveAgentRoomRequest $request Validated room data.
     * @param Room $room Room.
     * @return RedirectResponse Room list.
     */
    public function update(SaveAgentRoomRequest $request, Room $room): RedirectResponse
    {
        Gate::forUser($this->admin($request))->authorize('manageAsOwner', $room);
        $this->action->update($room, $request->validated());

        return redirect()->route('admin.rooms.index')->with('status', __('platform.rooms.updated', ['name' => $room->name]));
    }

    /**
     * Archive an owned room (frees a quota slot).
     *
     * @param Request $request Incoming request.
     * @param Room $room Room.
     * @return RedirectResponse Room list.
     */
    public function archive(Request $request, Room $room): RedirectResponse
    {
        Gate::forUser($this->admin($request))->authorize('manageAsOwner', $room);
        $this->action->archive($room);

        return redirect()->route('admin.rooms.index')->with('status', __('platform.rooms.archived', ['name' => $room->name]));
    }

    /**
     * Restore an archived owned room (takes a quota slot).
     *
     * @param Request $request Incoming request.
     * @param Room $room Room.
     * @return RedirectResponse Room list.
     */
    public function restore(Request $request, Room $room): RedirectResponse
    {
        $admin = $this->admin($request);
        Gate::forUser($admin)->authorize('manageAsOwner', $room);
        $this->action->restore($admin, $room);

        return redirect()->route('admin.rooms.index')->with('status', __('platform.rooms.restored', ['name' => $room->name]));
    }

    /**
     * Signed-in Agent.
     *
     * @param Request $request Incoming request.
     * @return Admin Agent.
     */
    private function admin(Request $request): Admin
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        return $admin;
    }
}
