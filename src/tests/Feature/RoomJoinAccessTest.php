<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomJoinAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reject guests on both the confirmation page and registration endpoint.
     *
     * @return void
     */
    public function test_guests_are_redirected_home_without_creating_memberships(): void
    {
        $room = $this->room();
        $url = route('user.rooms.join.show', $room->slug);

        $this->from('/me/rooms')->get($url)->assertRedirect(route('landing'));
        $this->post($url)->assertRedirect(route('landing'));
        $this->postJson($url)->assertRedirect(route('landing'));
        $this->assertDatabaseCount('room_users', 0);
    }

    /**
     * An admin session alone must not authorize room registration.
     *
     * @return void
     */
    public function test_admin_without_global_user_cannot_register(): void
    {
        $room = $this->room();
        $admin = AdminAccount::create([
            'name' => 'Admin', 'email' => 'join-admin@example.test', 'password' => 'secret',
            'role' => AdminRole::Admin, 'status' => AdminStatus::Active,
        ]);
        $this->actingAs($admin, 'admin');

        $this->get(route('user.rooms.join.show', $room->slug))->assertRedirect(route('landing'));
        $this->post(route('user.rooms.join', $room->slug))->assertRedirect(route('landing'));
        $this->assertDatabaseCount('room_users', 0);
    }

    /**
     * Existing members reach the dashboard without seeing registration again.
     *
     * @return void
     */
    public function test_existing_member_is_redirected_into_room(): void
    {
        $room = $this->room();
        $user = $this->user();
        $this->membership($room, $user);

        $this->actingAs($user, 'web')->get(route('user.rooms.join.show', $room->slug))
            ->assertRedirect(route('user.rooms.show', $room->slug));
        $this->get(route('user.rooms.show', $room->slug))->assertRedirect(route('user.dashboard', $room->slug));
        $this->assertDatabaseCount('room_users', 1);
    }

    /**
     * Confirm registration remains available for a global user who is not a member.
     *
     * @return void
     */
    public function test_new_global_user_sees_confirmation(): void
    {
        $room = $this->room();
        $this->actingAs($this->user(), 'web')->get(route('user.rooms.join.show', $room->slug))
            ->assertOk()->assertViewIs('user.join-room');
        $this->assertDatabaseCount('room_users', 0);
    }

    /**
     * Previously registered blocked members must retain their access restriction.
     *
     * @return void
     */
    public function test_blocked_member_cannot_bypass_restrictions_through_join(): void
    {
        $room = $this->room();
        $user = $this->user();
        $membership = $this->membership($room, $user);
        $membership->update(['status' => RoomUserStatus::Blocked]);

        $this->actingAs($user, 'web')->get(route('user.rooms.join.show', $room->slug))
            ->assertRedirect(route('user.rooms.show', $room->slug));
        $this->get(route('user.rooms.show', $room->slug))->assertForbidden();
        $this->post(route('user.rooms.join', $room->slug))->assertForbidden();
        $this->assertSame(RoomUserStatus::Blocked, $membership->fresh()->status);
    }

    /**
     * Inactive global accounts must not register or view confirmation.
     *
     * @return void
     */
    public function test_inactive_global_user_cannot_join(): void
    {
        $room = $this->room();
        $user = $this->user();
        $user->update(['status' => GlobalUserStatus::Blocked]);

        $this->actingAs($user, 'web')->get(route('user.rooms.join.show', $room->slug))->assertForbidden();
        $this->post(route('user.rooms.join', $room->slug))->assertForbidden();
        $this->assertDatabaseCount('room_users', 0);
    }

    /**
     * Create an active room for access tests.
     *
     * @return Room Target room.
     */
    private function room(): Room
    {
        return Room::create(['name' => 'Marketing', 'slug' => 'marketing', 'status' => RoomStatus::Active]);
    }

    /**
     * Create a global account for access tests.
     *
     * @return GlobalUser Authenticated account.
     */
    private function user(): GlobalUser
    {
        return GlobalUser::create([
            'name' => 'Join User', 'normalized_name' => 'JOIN USER',
            'email' => 'join-user@example.test', 'status' => GlobalUserStatus::Active,
        ]);
    }

    /**
     * Register an active member without issuing trusted device credentials.
     *
     * @param Room $room Target room.
     * @param GlobalUser $user Global account to register.
     * @return RoomUser Existing membership.
     */
    private function membership(Room $room, GlobalUser $user): RoomUser
    {
        return RoomUser::create([
            'room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'JOINUSER',
            'display_name' => $user->name, 'normalized_name' => $user->normalized_name,
            'status' => RoomUserStatus::Active, 'joined_at' => now(),
        ]);
    }
}
