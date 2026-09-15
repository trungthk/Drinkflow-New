<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Actions\Campaign\DeclineCampaignAction;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\Campaign\UserRoomCampaignService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Hiển thị danh sách chiến dịch hoặc menu món đang diễn ra trong phòng.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request
     * @param  \App\Services\Campaign\UserRoomCampaignService  $service  Service xử lý chiến dịch phòng
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View  Phản hồi JSON hoặc Giao diện View
     */
    public function index(Request $request, UserRoomCampaignService $service): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        if ($request->expectsJson() || $request->filled('q')) {
            $campaigns = $service->searchCampaigns($room, $request);
            return response()->json(['data' => $campaigns]);
        }

        $data = $service->getCampaignViewData($room, $roomUser, $user);

        return view('user.campaign', $data);
    }

    /**
     * Xem thông tin chi tiết một chiến dịch cụ thể trong phòng.
     *
     * @param  \App\Models\Campaign  $campaign  Chiến dịch cần xem
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Campaign $campaign, Request $request): JsonResponse|View|RedirectResponse
    {
        $room = $request->attributes->get('room');
        abort_unless($campaign->room_id === $room->id && in_array($campaign->status?->value, ['active', 'scheduled'], true), 404);

        if ($request->expectsJson()) {
            return response()->json(['data' => $campaign->load(['items' => fn ($q) => $q->where('status', 'active')->with(['sizes', 'toppings']), 'room'])]);
        }

        return redirect()->route('user.campaigns.index', $room->slug);
    }

    /**
     * Record that the authenticated room member will not participate in a campaign.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Current room.
     * @param Campaign $campaign Campaign being declined.
     * @param DeclineCampaignAction $action Participation action.
     * @return JsonResponse Declined participation payload.
     */
    public function decline(Request $request, \App\Models\Room $room, Campaign $campaign, DeclineCampaignAction $action): JsonResponse
    {
        /** @var \App\Models\RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        abort_unless($campaign->room_id === $room->id, 404);

        return response()->json(['data' => $action->execute($campaign, $roomUser)]);
    }
}
