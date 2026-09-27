<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CampaignOrderPageController extends Controller
{
    /**
     * Hiển thị trang giao diện chọn món và đặt hàng trong chiến dịch của phòng.
     *
     * Khi admin đã khóa chiến dịch, chuyển về trang danh sách chiến dịch (có thông báo đã khóa).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\Room  $room  Phòng chứa chiến dịch
     * @param  \App\Models\Campaign  $campaign  Chiến dịch đồ uống
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse  Giao diện đặt món hoặc chuyển hướng khi bị khóa
     */
    public function __invoke(Request $request, Room $room, Campaign $campaign): View|RedirectResponse
    {
        abort_unless($campaign->room_id === $room->id && in_array($campaign->status?->value, ['active', 'scheduled'], true), 404);

        if ($campaign->isOrderingLocked()) {
            return redirect()->route('user.campaigns.index', $room->slug);
        }

        return view('user.campaign-order', ['room' => $room, 'campaign' => $campaign]);
    }
}
