<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Notification\UserRoomNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomNotificationController extends Controller
{
    /**
     * Hiển thị danh sách thông báo của người dùng trong phòng hoặc trả về JSON API (/rooms/{slug}/notifications).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Services\Notification\UserRoomNotificationService  $service  Service xử lý thông báo phòng
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse  Giao diện View hoặc phản hồi JSON
     */
    public function index(Request $request, Room $room, UserRoomNotificationService $service): View|JsonResponse
    {
        $room = $request->attributes->get('room') ?? $room;
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $result = $service->getRoomNotificationData($room, $roomUser, $user, $request);

        if ($result['is_json']) {
            return response()->json($result['data']);
        }

        return view('user.notifications', $result['view_data']);
    }
}

