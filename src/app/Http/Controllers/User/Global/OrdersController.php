<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Services\Order\UserOrdersService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * Chuyển tiếp request đến phương thức hiển thị lịch sử đơn hàng.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Order\UserOrdersService  $service  Service xử lý dữ liệu đơn hàng
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse  Giao diện View hoặc phản hồi JSON
     */
    public function __invoke(Request $request, UserOrdersService $service): View|JsonResponse
    {
        return $this->index($request, $service);
    }

    /**
     * Hiển thị trang lịch sử đơn hàng toàn hệ thống hoặc trả về JSON API (/me/orders).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Order\UserOrdersService  $service  Service xử lý dữ liệu đơn hàng
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse  Giao diện View hoặc phản hồi JSON
     */
    public function index(Request $request, UserOrdersService $service): View|JsonResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        if ($request->wantsJson()) {
            return $this->api($request, $service);
        }

        $data = $service->getOrdersPageData($user, $request);

        return view('user.global.orders', $data);
    }

    /**
     * Cung cấp endpoint JSON API truy vấn lịch sử đơn hàng phân trang kèm các tham số lọc.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa bộ lọc
     * @param  \App\Services\Order\UserOrdersService  $service  Service xử lý dữ liệu đơn hàng
     * @return \Illuminate\Http\JsonResponse  Dữ liệu JSON phân trang
     */
    public function api(Request $request, UserOrdersService $service): JsonResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $orders = $service->getOrdersApiData($user, $request);

        return response()->json(['data' => $orders]);
    }
}

