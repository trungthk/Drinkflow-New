<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCampaignSponsorFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The campaign list filters by sponsor type, shows the total and marks sponsored campaigns.
     *
     * @return void
     */
    public function test_campaign_list_filters_by_sponsor_type_and_marks_sponsored_campaigns(): void
    {
        $admin = AdminAccount::create(['name' => 'Sponsor Admin', 'email' => 'sponsor-filter@example.test', 'password' => Hash::make('secret'), 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'Sponsor Room', 'slug' => 'sponsor-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        Campaign::create(['room_id' => $room->id, 'name' => 'Self Paid Tea', 'restaurant' => 'Cafe', 'sponsor_type' => Campaign::SPONSOR_TYPE_NONE, 'status' => CampaignStatus::Closed]);
        Campaign::create(['room_id' => $room->id, 'name' => 'Boss Treats Coffee', 'restaurant' => 'Cafe', 'sponsor_type' => Campaign::SPONSOR_TYPE_FULL, 'sponsor_name' => 'Big Boss', 'status' => CampaignStatus::Closed]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.campaigns.page', $room))
            ->assertOk()
            ->assertSeeText(__('admin.total_campaigns_badge', ['count' => 2]))
            ->assertSee('Self Paid Tea')
            ->assertSee('Boss Treats Coffee')
            ->assertSee('data-tooltip="'.e(__('admin.sponsor_type_full').' · Big Boss').'"', false)
            ->assertSee('data-sponsor-icon', false);

        $this->get(route('admin.campaigns.page', ['room' => $room, 'sponsor_type' => Campaign::SPONSOR_TYPE_FULL]))
            ->assertOk()
            ->assertSeeText(__('admin.total_campaigns_badge', ['count' => 1]))
            ->assertSee('Boss Treats Coffee')
            ->assertDontSee('Self Paid Tea')
            ->assertSee('id="campaign-clear-filters"', false);

        $this->get(route('admin.campaigns.page', ['room' => $room, 'sponsor_type' => Campaign::SPONSOR_TYPE_NONE]))
            ->assertOk()
            ->assertSee('Self Paid Tea')
            ->assertDontSee('Boss Treats Coffee')
            ->assertDontSee('data-sponsor-icon', false);

        $this->get(route('admin.campaigns.page', ['room' => $room, 'sponsor_type' => 'bogus']))
            ->assertSessionHasErrors('sponsor_type');
    }
}
