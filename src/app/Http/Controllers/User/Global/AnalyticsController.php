<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Exports\AnalyticsReportExport;
use App\Services\Analytics\UserGlobalAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalyticsController extends Controller
{
    /**
     * Download personal analytics using the same data as the statistics page.
     *
     * @param Request $request Authenticated request.
     * @param UserGlobalAnalyticsService $service Personal analytics service.
     * @return BinaryFileResponse Excel report attachment.
     */
    public function export(Request $request, UserGlobalAnalyticsService $service): BinaryFileResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        return Excel::download(
            new AnalyticsReportExport($service->getAnalyticsViewData($user)),
            'drinkflow-statistics-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    /**
     * Điều hướng hiển thị giao diện phân tích số liệu hoặc trả về JSON API tùy theo Accept header hoặc Route.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Analytics\UserGlobalAnalyticsService  $service  Service xử lý dữ liệu thống kê
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View  Phản hồi JSON hoặc Giao diện View
     */
    public function __invoke(Request $request, UserGlobalAnalyticsService $service): JsonResponse|View
    {
        if ($request->expectsJson() || $request->routeIs('user.analytics.global')) {
            return $this->global($request, $service);
        }

        return $this->view($request, $service);
    }

    /**
     * Hiển thị trang giao diện thống kê chi tiêu cá nhân toàn hệ thống (/me/statistics).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Analytics\UserGlobalAnalyticsService  $service  Service xử lý dữ liệu thống kê
     * @return \Illuminate\Contracts\View\View  Giao diện View thống kê
     */
    public function view(Request $request, UserGlobalAnalyticsService $service): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $data = $service->getAnalyticsViewData($user);

        return view('user.global.statistics', $data);
    }

    /**
     * Trả về dữ liệu thống kê chi tiêu và top món dưới dạng JSON API.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Analytics\UserGlobalAnalyticsService  $service  Service xử lý dữ liệu thống kê
     * @return \Illuminate\Http\JsonResponse  Dữ liệu JSON
     */
    public function global(Request $request, UserGlobalAnalyticsService $service): JsonResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $data = $service->getAnalyticsApiData($user);

        return response()->json(['data' => $data]);
    }
}
