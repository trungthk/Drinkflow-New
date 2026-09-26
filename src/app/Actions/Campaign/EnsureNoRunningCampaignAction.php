<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Validation\ValidationException;

class EnsureNoRunningCampaignAction
{
    /**
     * Guarantee a room has no other running campaign before creating or activating one.
     *
     * Must be called inside a DB transaction: the room row is locked so two concurrent requests
     * cannot both pass the check and leave the room with two running campaigns.
     *
     * @param int $roomId Room being checked.
     * @param int|null $exceptCampaignId Campaign allowed to be running (the one being activated/updated).
     * @return void
     *
     * @throws ValidationException When another campaign in the room is still active or closing.
     */
    public function execute(int $roomId, ?int $exceptCampaignId = null): void
    {
        Room::query()->whereKey($roomId)->lockForUpdate()->first();

        $exists = Campaign::query()
            ->where('room_id', $roomId)
            ->whereIn('status', CampaignStatus::running())
            ->when($exceptCampaignId !== null, static fn ($query) => $query->whereKeyNot($exceptCampaignId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'campaign' => __('admin.campaign_running_exists'),
            ]);
        }
    }
}
