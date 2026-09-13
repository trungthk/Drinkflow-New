<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Analytics\UserRoomAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Hiển thị bảng phân tích thống kê hoạt động của người dùng trong phòng hoặc trả về JSON API (/rooms/{slug}/analytics).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Analytics\UserRoomAnalyticsService  $service  Service xử lý thống kê phòng
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View  Phản hồi JSON hoặc Giao diện View
     */
    public function room(Request $request, UserRoomAnalyticsService $service): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $result = $service->getRoomAnalyticsData($room, $roomUser, $user, $request);

        if ($result['is_json']) {
            return response()->json(['data' => $result['data']]);
        }

        return view('user.analytics', $result['view_data']);
    }
}

