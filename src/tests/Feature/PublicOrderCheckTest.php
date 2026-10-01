<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Order\PublicOrderCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicOrderCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the signed page and lookup URLs for a campaign.
     *
     * @param Campaign $campaign Campaign to link.
     * @return array{0: string, 1: string} Page URL and lookup URL.
     */
    private function urls(Campaign $campaign): array
    {
        $params = ['campaign' => $campaign->id, 'hash' => app(PublicOrderCheckService::class)->hash($campaign)];

        return [
            URL::temporarySignedRoute('public.order-check', now()->addDay(), $params),
            URL::temporarySignedRoute('public.order-check.lookup', now()->addDay(), $params),
        ];
    }

    /** Every campaign except drafts and cancelled ones can be checked. */
    public function test_order_check_is_available_for_all_statuses_except_draft_and_cancelled(): void
    {
        $room = Room::create(['name' => 'Check Room', 'slug' => 'check-room', 'status' => 'active']);

        foreach (CampaignStatus::cases() as $status) {
            $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Coffee '.$status->value, 'restaurant' => 'Cafe', 'status' => $status]);
            [$pageUrl] = $this->urls($campaign);
            $expected = in_array($status, [CampaignStatus::Draft, CampaignStatus::Cancelled], true) ? 404 : 200;

            $this->get($pageUrl)->assertStatus($expected);
        }
    }

    /** The page uses its own OG image and the lookup returns the campaign status for the info block. */
    public function test_lookup_returns_campaign_status_and_page_has_og_image(): void
    {
        $room = Room::create(['name' => 'Check Room', 'slug' => 'check-room-status', 'status' => 'active']);
        $campaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'Afternoon tea', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'deadline' => now()->addHour(),
        ]);
        $user = GlobalUser::create(['name' => 'Lan', 'email' => 'lan@example.test', 'status' => 'active']);
        $member = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'display_name' => 'Lan', 'status' => 'active']);
        Order::create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $member->id,
            'subtotal' => 20000, 'final_amount' => 20000, 'status' => OrderStatus::Submitted,
        ]);
        [$pageUrl, $lookupUrl] = $this->urls($campaign);

        $this->get($pageUrl)
            ->assertOk()
            ->assertSee(asset('images/og-order-check.jpg'), false)
            ->assertSee('data-campaign-status', false);

        $this->postJson($lookupUrl, ['identifier' => 'lan@example.test'])
            ->assertOk()
            ->assertJsonPath('campaign.status', CampaignStatus::Active->value)
            ->assertJsonPath('campaign.status_label', CampaignStatus::Active->label());
    }
}
