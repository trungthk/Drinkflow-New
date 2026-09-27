<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Support\Collection;

class PreviousCampaignMenuService
{
    /** Number of closed campaigns shown at first and added by each "Xem thêm" click. */
    public const PAGE_SIZE = 4;

    /** Upper bound for a single page requested through the API. */
    public const MAX_PAGE_SIZE = 20;

    /**
     * Fetch one page of the room's closed campaigns that have a menu, newest first, for menu reuse.
     *
     * @param Room $room Room whose campaigns are listed.
     * @param int $offset Number of campaigns to skip.
     * @param int $limit Page size (clamped to 1..MAX_PAGE_SIZE).
     * @param int|null $excludeCampaignId Campaign to leave out (the one being edited).
     * @return array{campaigns: Collection<int, Campaign>, has_more: bool, next_offset: int} Page of campaigns with menu items loaded.
     */
    public function page(Room $room, int $offset = 0, int $limit = self::PAGE_SIZE, ?int $excludeCampaignId = null): array
    {
        $offset = max(0, $offset);
        $limit = min(self::MAX_PAGE_SIZE, max(1, $limit));

        // Fetch one extra row to know whether another page exists without a separate count query.
        $rows = Campaign::query()
            ->where('room_id', $room->id)
            ->where('status', CampaignStatus::Closed)
            ->when($excludeCampaignId !== null, static fn ($query) => $query->whereKeyNot($excludeCampaignId))
            ->whereHas('items')
            ->with(['items.sizes', 'items.toppings'])
            ->latest()
            ->latest('id')
            ->skip($offset)
            ->take($limit + 1)
            ->get();

        $campaigns = $rows->take($limit)->values()->each(static function (Campaign $campaign): void {
            // Display label formatted server-side so every page renders the same local time.
            $campaign->setAttribute('created_label', $campaign->created_at?->format('d/m/Y H:i'));
        });

        return [
            'campaigns' => $campaigns,
            'has_more' => $rows->count() > $limit,
            'next_offset' => $offset + $campaigns->count(),
        ];
    }
}
