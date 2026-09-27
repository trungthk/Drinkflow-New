<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PreviousCampaignMenusTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccount $admin;

    private Room $room;

    /** @var list<Campaign> Closed campaigns, newest first. */
    private array $closed = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'prev-menus@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Prev Room', 'slug' => 'prev-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);

        // 6 closed campaigns with a menu (#6 newest), plus noise that must never be listed.
        for ($i = 1; $i <= 6; $i++) {
            $this->closed[] = $this->campaignWithItem("Closed {$i}", 'closed', now()->subDays(10 - $i));
        }
        $this->closed = array_reverse($this->closed);
        $this->campaignWithItem('Active one', 'active', now());
        Campaign::create(['room_id' => $this->room->id, 'name' => 'Closed without menu', 'restaurant' => 'Cafe', 'status' => 'closed']);
    }

    private function campaignWithItem(string $name, string $status, \DateTimeInterface $createdAt): Campaign
    {
        $campaign = Campaign::create(['room_id' => $this->room->id, 'name' => $name, 'restaurant' => 'Cafe', 'status' => $status]);
        $campaign->forceFill(['created_at' => $createdAt])->save();
        $campaign->items()->create(['name' => 'Trà sữa', 'normalized_name' => 'tra sua', 'base_price' => 30000, 'status' => 'active']);

        return $campaign;
    }

    public function test_api_pages_closed_campaigns_four_at_a_time(): void
    {
        $url = route('admin.campaigns.previous-menus', $this->room);

        $this->actingAs($this->admin, 'admin')->getJson($url)
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.name', 'Closed 6')
            ->assertJsonPath('data.3.name', 'Closed 3')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('meta.next_offset', 4);

        $this->actingAs($this->admin, 'admin')->getJson($url.'?offset=4')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Closed 2')
            ->assertJsonPath('data.1.name', 'Closed 1')
            ->assertJsonPath('meta.has_more', false);
    }

    public function test_api_can_exclude_the_campaign_being_edited(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.campaigns.previous-menus', $this->room).'?exclude='.$this->closed[0]->id)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Closed 5')
            ->assertJsonMissing(['name' => 'Closed 6']);
    }

    public function test_create_page_shows_first_four_and_load_more_button(): void
    {
        // A room with a running campaign is redirected away from the create page.
        Campaign::query()->where('name', 'Active one')->update(['status' => 'draft']);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.campaigns.create', $this->room))
            ->assertOk()
            ->assertSee('data-previous-campaign-load-more', false)
            ->assertSee(__('admin.previous_campaigns_load_more'))
            ->getContent();

        $this->assertStringContainsString('Closed 6', $html);
        $this->assertStringContainsString('Closed 3', $html);
        $this->assertStringNotContainsString('Closed 2', $html);
    }
}
