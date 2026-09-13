<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Public\VersionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    /**
     * Hiển thị trang nhật ký thay đổi phiên bản của hệ thống DrinkFlow.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Public\VersionService  $versionService  Service xử lý logic dữ liệu phiên bản
     * @param  string|null  $version  Tên phiên bản cụ thể (hoặc null nếu xem phiên bản mới nhất)
     * @return \Illuminate\Contracts\View\View  Giao diện hiển thị danh sách và chi tiết phiên bản
     */
    public function __invoke(Request $request, VersionService $versionService, ?string $version = null): View
    {
        $data = $versionService->getVersionPageData($version);

        return view('public.versions', $data);
    }
}

