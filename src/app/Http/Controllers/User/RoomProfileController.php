<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\User\UserRoomProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomProfileController extends Controller
{
    /**
     * Hiển thị trang thông tin cá nhân và thống kê của người dùng trong phòng hoặc trả về JSON API (/rooms/{slug}/profile).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Services\User\UserRoomProfileService  $service  Service xử lý hồ sơ phòng
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse  Giao diện View hoặc phản hồi JSON
     */
    public function index(Request $request, Room $room, UserRoomProfileService $service): View|JsonResponse
    {
        $room = $request->attributes->get('room') ?? $room;
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $result = $service->getRoomProfileData($room, $roomUser, $user, $request);

        if ($result['is_json']) {
            return response()->json($result['data']);
        }

        return view('user.profile', $result['view_data']);
    }
}

