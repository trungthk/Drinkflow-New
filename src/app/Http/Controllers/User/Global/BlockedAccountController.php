<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Services\User\UserBlockedService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlockedAccountController extends Controller
{
    /**
     * Hiển thị trang thông báo tài khoản bị khóa và thống kê công nợ chưa trả (/me/blocked).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\User\UserBlockedService  $service  Service xử lý dữ liệu tài khoản bị khóa
     * @return \Illuminate\Contracts\View\View  Giao diện thông báo khóa tài khoản
     */
    public function show(Request $request, UserBlockedService $service): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $data = $service->getBlockedNoticeData($user);

        return view('user.global.blocked', $data);
    }

    /**
     * Tiếp nhận yêu cầu khiếu nại hoặc giải trình mở khóa tài khoản từ người dùng.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa lý do khiếu nại
     * @return \Illuminate\Http\RedirectResponse  Phản hồi chuyển hướng kèm thông báo tiếp nhận
     */
    public function appeal(Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'attachment_note' => 'nullable|string|max:500',
        ]);

        return back()->with('status', __('global.blocked.appeal_submitted_status'));
    }
}

