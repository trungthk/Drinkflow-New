<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Admin;
use App\Models\Package;
use App\Models\Room;
use App\Services\Room\RoomQuotaService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T21–T25: Agents manage their own rooms within the subscription quota, enforced server-side.
 */
class AgentRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = $this->agentWithQuota('agent@drinkflow.test', 2);
    }

    private function agentWithQuota(string $email, ?int $limit): Admin
    {
        $admin = Admin::create(['name' => 'Agent '.$email, 'email' => $email, 'password' => 'password123', 'status' => 'active']);
        if ($limit !== null) {
            $package = Package::create(['code' => 'p'.$limit.'-'.md5($email), 'name' => 'Plan', 'monthly_price' => 100000, 'room_limit' => $limit, 'status' => 'active']);
            app(SubscriptionService::class)->activate($admin, $package);
        }

        return $admin;
    }

    /**
     * Room form data.
     *
     * @param array<string, mixed> $overrides Overrides.
     * @return array<string, mixed> Form data.
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + ['name' => 'Team Sales', 'slug' => '', 'description' => 'Sales floor', 'timezone' => 'Asia/Ho_Chi_Minh', 'language' => 'vi'];
    }

    private function ownedRoom(string $slug, string $status = 'active', ?Admin $owner = null): Room
    {
        $owner ??= $this->agent;
        $room = Room::create(['name' => 'Room '.$slug, 'slug' => $slug, 'status' => $status, 'owner_admin_id' => $owner->id]);
        $room->admins()->attach($owner->id);

        return $room;
    }

    public function test_agent_creates_an_owned_room(): void
    {
        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.store'), $this->payload())->assertRedirect(route('admin.rooms.index'));

        $room = Room::query()->where('slug', 'team-sales')->firstOrFail();
        $this->assertSame($this->agent->id, $room->owner_admin_id);
        $this->assertSame(RoomStatus::Active, $room->status);
        $this->assertTrue($this->agent->rooms()->whereKey($room->id)->exists());
        $this->assertDatabaseHas('audit_logs', ['event' => 'room.created', 'target_id' => $room->id]);
        // The new room is usable in the room area right away.
        $this->actingAs($this->agent, 'admin')->get(route('admin.dashboard.page', $room))->assertOk();
    }

    public function test_room_list_shows_usage_and_rooms(): void
    {
        $this->ownedRoom('alpha');
        $this->ownedRoom('old', 'archived');

        $this->actingAs($this->agent, 'admin')->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee(__('platform.rooms.quota_usage', ['used' => 1, 'limit' => 2]))
            ->assertSee('/alpha')
            ->assertSee('/old');
    }

    public function test_create_is_refused_server_side_when_the_quota_is_full(): void
    {
        $this->ownedRoom('one');
        $this->ownedRoom('two', 'inactive');

        $this->actingAs($this->agent, 'admin')->get(route('admin.rooms.index'))->assertOk()->assertSee('data-room-quota="full"', false);
        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.store'), $this->payload())
            ->assertSessionHasErrors(['room' => __('platform.rooms.quota_reached', ['limit' => 2])]);
        $this->assertSame(2, Room::query()->count());
    }

    public function test_agent_without_subscription_cannot_create_rooms(): void
    {
        $legacy = $this->agentWithQuota('legacy@drinkflow.test', null);

        $this->actingAs($legacy, 'admin')->post(route('admin.rooms.store'), $this->payload())
            ->assertSessionHasErrors(['room' => __('platform.rooms.no_subscription')]);
        $this->assertSame(0, Room::query()->count());
    }

    public function test_quota_counts_active_and_disabled_rooms_but_not_archived(): void
    {
        $this->ownedRoom('active');
        $this->ownedRoom('disabled', 'inactive');
        $this->ownedRoom('archived', 'archived');
        // A room shared with the Agent is not theirs and does not count.
        $other = $this->agentWithQuota('other@drinkflow.test', 5);
        $this->ownedRoom('foreign', 'active', $other)->admins()->attach($this->agent->id);

        $usage = app(RoomQuotaService::class)->usage($this->agent);

        $this->assertSame(2, $usage['used']);
        $this->assertSame(2, $usage['limit']);
        $this->assertSame('full', $usage['state']);
    }

    public function test_quota_comes_from_the_subscription_snapshot(): void
    {
        $package = $this->agent->activeSubscription->package;
        $package->update(['room_limit' => 100]);

        $this->assertSame(2, app(RoomQuotaService::class)->limit($this->agent));
    }

    public function test_archive_frees_a_slot_and_restore_needs_one(): void
    {
        $first = $this->ownedRoom('first');
        $this->ownedRoom('second');

        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.archive', $first))->assertRedirect(route('admin.rooms.index'));
        $this->assertSame(RoomStatus::Archived, $first->fresh()->status);
        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.store'), $this->payload(['slug' => 'third']))->assertSessionHasNoErrors();

        // Quota full again: restoring the archived room is refused.
        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.restore', $first))->assertSessionHasErrors('room');
        $this->assertSame(RoomStatus::Archived, $first->fresh()->status);

        Room::query()->where('slug', 'third')->update(['status' => 'archived']);
        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.restore', $first))->assertRedirect(route('admin.rooms.index'));
        $this->assertSame(RoomStatus::Active, $first->fresh()->status);
    }

    public function test_agent_edits_and_disables_an_owned_room(): void
    {
        $room = $this->ownedRoom('edit-me');

        $this->actingAs($this->agent, 'admin')->get(route('admin.rooms.edit', $room))->assertOk();
        $this->actingAs($this->agent, 'admin')->put(route('admin.rooms.update', $room), $this->payload(['name' => 'Renamed', 'slug' => 'edit-me', 'status' => 'inactive']))
            ->assertRedirect(route('admin.rooms.index'));

        $room->refresh();
        $this->assertSame('Renamed', $room->name);
        $this->assertSame(RoomStatus::Inactive, $room->status);
        // Archiving goes through its own action, never through the edit form.
        $this->actingAs($this->agent, 'admin')->put(route('admin.rooms.update', $room), $this->payload(['slug' => 'edit-me', 'status' => 'archived']))->assertSessionHasErrors('status');
    }

    public function test_reserved_and_duplicate_slugs_are_rejected(): void
    {
        $this->ownedRoom('taken');

        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.store'), $this->payload(['slug' => 'rooms']))->assertSessionHasErrors('slug');
        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.store'), $this->payload(['slug' => 'taken']))->assertSessionHasErrors('slug');
    }

    public function test_room_with_outstanding_debt_or_running_campaign_cannot_be_archived(): void
    {
        $room = $this->ownedRoom('busy');
        \App\Models\Campaign::create(['room_id' => $room->id, 'name' => 'Friday', 'restaurant' => 'Store', 'status' => \App\Enums\CampaignStatus::Active]);

        $this->actingAs($this->agent, 'admin')->post(route('admin.rooms.archive', $room))->assertSessionHasErrors(['room' => __('platform.rooms.running_campaign')]);
        $this->assertSame(RoomStatus::Active, $room->fresh()->status);
    }
}
