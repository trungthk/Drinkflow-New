<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Services\Dashboard\UserGlobalDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Hiển thị bảng điều khiển tổng quan toàn hệ thống của người dùng (/me).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Dashboard\UserGlobalDashboardService  $service  Service xử lý dữ liệu tổng hợp Dashboard
     * @return \Illuminate\Contracts\View\View  Giao diện Dashboard toàn hệ thống
     */
    public function __invoke(Request $request, UserGlobalDashboardService $service): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $data = $service->getDashboardData($user);

        return view('user.global.dashboard', $data);
    }
}

