<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Dashboard\UserRoomDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Room Dashboard view for authenticated room members.
     *
     * @param Request $request Current HTTP request instance.
     * @param UserRoomDashboardService $service Dashboard data aggregation service.
     * @return View Rendered dashboard view.
     */
    public function __invoke(Request $request, UserRoomDashboardService $service): View
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $data = $service->getDashboardData($room, $roomUser, $user);

        return view('user.dashboard', $data);
    }
}
