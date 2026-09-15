<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\RoomRealtimeEvent;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use App\Models\RoomUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeclineCampaignAction
{
    /**
     * Record that a room member declines an active campaign and notify the room.
     *
     * @param Campaign $campaign Campaign being declined.
     * @param RoomUser $roomUser Member declining the campaign.
     * @return CampaignParticipant Persisted participation record.
     * @throws ValidationException If the campaign does not belong to the member's room or is unavailable.
     */
    public function execute(Campaign $campaign, RoomUser $roomUser): CampaignParticipant
    {
        if ($campaign->room_id !== $roomUser->room_id || $campaign->status !== CampaignStatus::Active) {
            throw ValidationException::withMessages(['campaign' => __('admin.campaign_unavailable')]);
        }

        $participant = DB::transaction(function () use ($campaign, $roomUser): CampaignParticipant {
            return CampaignParticipant::query()->updateOrCreate(
                ['campaign_id' => $campaign->id, 'room_user_id' => $roomUser->id],
                ['status' => CampaignParticipant::STATUS_DECLINED, 'declined_at' => now()],
            );
        });

        RoomRealtimeEvent::dispatch('campaign.participant.declined', $campaign->room_id, [
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'status' => CampaignParticipant::STATUS_DECLINED,
        ]);

        return $participant;
    }
}
