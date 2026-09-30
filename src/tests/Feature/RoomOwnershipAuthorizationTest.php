<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionScope;
use App\Models\Admin;
use App\Models\Package;
use App\Models\PermissionRecord;
use App\Models\Room;
use App\Models\Superadmin;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T26: room authorization follows ownership — no cross-Agent access from admins, realtime or scoped Superadmins.
 */
class RoomOwnershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $alice;

    private Admin $bob;

    private Room $aliceRoom;

    private Room $bobRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $package = Package::create(['code' => 'plan', 'name' => 'Plan', 'monthly_price' => 1, 'room_limit' => 5, 'status' => 'active']);
        $this->alice = Admin::create(['name' => 'Alice', 'email' => 'alice@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $this->bob = Admin::create(['name' => 'Bob', 'email' => 'bob@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        app(SubscriptionService::class)->activate($this->alice, $package);
        app(SubscriptionService::class)->activate($this->bob, $package);
        $this->aliceRoom = $this->room('alice-room', $this->alice);
        $this->bobRoom = $this->room('bob-room', $this->bob);
    }

    private function room(string $slug, Admin $owner): Room
    {
        $room = Room::create(['name' => $slug, 'slug' => $slug, 'status' => 'active', 'owner_admin_id' => $owner->id]);
        $room->admins()->attach($owner->id);

        return $room;
    }

    /**
     * Superadmin with the room permissions limited to the given scope.
     *
     * @param PermissionScope $scope Scope.
     * @return Superadmin Superadmin.
     */
    private function roomSuperadmin(PermissionScope $scope): Superadmin
    {
        $superadmin = Superadmin::create(['name' => 'Scoped', 'email' => 'scoped@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        foreach (['room.view', 'room.manage'] as $key) {
            $superadmin->permissions()->attach(PermissionRecord::query()->where('key', $key)->value('id'), ['scope' => $scope->value]);
        }

        return $superadmin;
    }

    public function test_agent_cannot_reach_another_agents_room(): void
    {
        $this->actingAs($this->alice, 'admin')->getJson(route('admin.dashboard', $this->bobRoom))->assertForbidden();
        $this->actingAs($this->alice, 'admin')->getJson(route('admin.socket-token', $this->bobRoom))->assertForbidden();
        $this->actingAs($this->alice, 'admin')->get(route('admin.rooms.edit', $this->bobRoom))->assertForbidden();
        $this->actingAs($this->alice, 'admin')->put(route('admin.rooms.update', $this->bobRoom), ['name' => 'X', 'slug' => 'bob-room', 'timezone' => 'UTC', 'language' => 'vi'])->assertForbidden();
        $this->actingAs($this->alice, 'admin')->post(route('admin.rooms.archive', $this->bobRoom))->assertForbidden();
        $this->assertSame('active', $this->bobRoom->fresh()->status->value);
    }

    public function test_owner_operates_its_room_and_gets_a_socket_token(): void
    {
        $this->actingAs($this->alice, 'admin')->getJson(route('admin.dashboard', $this->aliceRoom))->assertOk();
        $this->actingAs($this->alice, 'admin')->getJson(route('admin.socket-token', $this->aliceRoom))->assertOk();
    }

    public function test_collaborator_operates_but_cannot_manage_the_room(): void
    {
        $this->bobRoom->admins()->attach($this->alice->id);

        $this->actingAs($this->alice, 'admin')->getJson(route('admin.dashboard', $this->bobRoom))->assertOk();
        $this->actingAs($this->alice, 'admin')->get(route('admin.rooms.edit', $this->bobRoom))->assertForbidden();
        $this->actingAs($this->alice, 'admin')->post(route('admin.rooms.archive', $this->bobRoom))->assertForbidden();
    }

    public function test_suspended_owner_loses_access(): void
    {
        $this->alice->update(['status' => 'suspended']);

        $this->actingAs($this->alice->fresh(), 'admin')->getJson(route('admin.dashboard', $this->aliceRoom))->assertForbidden();
    }

    public function test_managed_superadmin_sees_rooms_owned_by_assigned_agents_only(): void
    {
        $superadmin = $this->roomSuperadmin(PermissionScope::Managed);
        $superadmin->managedAdmins()->attach($this->alice->id, ['is_primary' => true, 'assigned_at' => now()]);
        // Alice collaborates in Bob's room: that does not make Bob's room visible.
        $this->bobRoom->admins()->attach($this->alice->id);

        $slugs = collect($this->actingAs($superadmin, 'superadmin')->getJson(route('superadmin.rooms.index'))->assertOk()->json('data.data'))->pluck('slug')->all();

        $this->assertSame(['alice-room'], $slugs);
        $this->actingAs($superadmin, 'superadmin')->getJson(route('superadmin.rooms.show', $this->bobRoom))->assertForbidden();
        $this->actingAs($superadmin, 'superadmin')->getJson(route('superadmin.rooms.show', $this->aliceRoom))->assertOk();
    }

    public function test_legacy_room_without_owner_falls_back_to_assigned_admins(): void
    {
        $legacy = Room::create(['name' => 'Legacy', 'slug' => 'legacy', 'status' => 'active']);
        $legacy->admins()->attach($this->alice->id);
        $superadmin = $this->roomSuperadmin(PermissionScope::Managed);
        $superadmin->managedAdmins()->attach($this->alice->id, ['is_primary' => true, 'assigned_at' => now()]);

        $this->actingAs($superadmin, 'superadmin')->getJson(route('superadmin.rooms.show', $legacy))->assertOk();
    }

    public function test_superadmin_cannot_remove_the_owner_from_its_room(): void
    {
        $owner = $this->createSuperadmin(['name' => 'Owner', 'email' => 'owner@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $this->bobRoom->admins()->attach($this->alice->id);

        $this->actingAs($owner, 'superadmin')->putJson(route('superadmin.rooms.update', $this->bobRoom), ['admin_ids' => [$this->alice->id]])->assertOk();

        $this->assertEqualsCanonicalizing([$this->alice->id, $this->bob->id], $this->bobRoom->admins()->pluck('admins.id')->all());
        $this->actingAs($owner, 'superadmin')->putJson(route('superadmin.admins.rooms', $this->bob), ['room_ids' => []])->assertOk();
        $this->assertTrue($this->bob->rooms()->whereKey($this->bobRoom->id)->exists());
    }

    public function test_superadmin_can_transfer_ownership_within_scope(): void
    {
        $owner = $this->createSuperadmin(['name' => 'Owner', 'email' => 'owner@drinkflow.test', 'password' => 'password123', 'status' => 'active']);

        $this->actingAs($owner, 'superadmin')->putJson(route('superadmin.rooms.update', $this->bobRoom), ['owner_admin_id' => $this->alice->id])->assertOk();

        $this->assertSame($this->alice->id, $this->bobRoom->fresh()->owner_admin_id);
        $this->assertTrue($this->alice->rooms()->whereKey($this->bobRoom->id)->exists());
    }

    public function test_managed_superadmin_cannot_give_a_room_to_an_agent_outside_scope(): void
    {
        $superadmin = $this->roomSuperadmin(PermissionScope::Managed);
        $superadmin->managedAdmins()->attach($this->alice->id, ['is_primary' => true, 'assigned_at' => now()]);

        $this->actingAs($superadmin, 'superadmin')->putJson(route('superadmin.rooms.update', $this->aliceRoom), ['owner_admin_id' => $this->bob->id])->assertUnprocessable();
        $this->assertSame($this->alice->id, $this->aliceRoom->fresh()->owner_admin_id);
    }
}
