<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\RoomRealtimeEvent;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use App\Models\RoomUser;
use Illuminate\Validation\ValidationException;

class RejoinCampaignAction
{
    /**
     * Remove the member's declined record and notify the room of the rejoin.
     *
     * @param Campaign $campaign Campaign being rejoined.
     * @param RoomUser $roomUser Member rejoining the campaign.
     * @return void
     * @throws ValidationException If the campaign is unavailable.
     */
    public function execute(Campaign $campaign, RoomUser $roomUser): void
    {
        if ($campaign->room_id !== $roomUser->room_id || $campaign->status !== CampaignStatus::Active) {
            throw ValidationException::withMessages(['campaign' => __('admin.campaign_unavailable')]);
        }

        CampaignParticipant::query()
            ->where('campaign_id', $campaign->id)
            ->where('room_user_id', $roomUser->id)
            ->where('status', CampaignParticipant::STATUS_DECLINED)
            ->delete();

        RoomRealtimeEvent::dispatch('campaign.participant.rejoined', $campaign->room_id, [
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'status' => 'pending',
        ]);
    }
}