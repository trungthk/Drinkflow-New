<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CampaignOrderPageController extends Controller
{
    public function __invoke(Request $request, Campaign $campaign): View
    {
        abort_unless($campaign->room_id === $request->attributes->get('room')->id && in_array($campaign->status?->value, ['active', 'scheduled'], true), 404);

        return view('user.campaign-order', ['room' => $request->attributes->get('room'), 'campaign' => $campaign]);
    }
}
