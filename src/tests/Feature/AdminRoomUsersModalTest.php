<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRoomUsersModalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The shared user action modal ships an icon in the header and an icon inside the confirm button.
     */
    public function test_user_action_modal_has_header_and_confirm_icons(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'users-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Users Room', 'slug' => 'users-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.room-users.page', $room->slug))
            ->assertOk()
            ->assertSee('id="user-action-icon-wrap"', false)
            ->assertSee('id="user-action-icon"', false)
            ->assertSee('id="user-action-confirm-icon"', false)
            ->assertSee('id="user-action-confirm-label"', false)
            ->assertSee('data-confirm-label="'.__('admin.confirm_action').'"', false);

        $html = $response->getContent();
        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
    }

    /**
     * Removed members get a restore button, and restoring puts them back to active.
     */
    public function test_admin_can_restore_removed_member(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'restore-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Users Room', 'slug' => 'users-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $member = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => GlobalUser::create(['name' => 'Gone Member', 'email' => 'gone@example.test', 'status' => 'active'])->id,
            'display_name' => 'Gone Member',
            'status' => RoomUserStatus::Removed,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.room-users.page', $room->slug))
            ->assertOk()
            ->assertSee('data-restore-room-user', false);

        $url = route('admin.room-users.restore', [$room->slug, $member->id]);
        $this->actingAs($admin, 'admin')->postJson($url)->assertOk();

        $this->assertSame(RoomUserStatus::Active, $member->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'room_user.status_updated', 'target_id' => $member->id]);

        // Restoring a member that is not removed is rejected.
        $this->actingAs($admin, 'admin')->postJson($url)->assertStatus(422)->assertJsonValidationErrors('room_user');

        // A member of another room cannot be restored through this room.
        $otherRoom = Room::create(['name' => 'Other', 'slug' => 'other-room', 'status' => 'active']);
        $foreign = RoomUser::create([
            'room_id' => $otherRoom->id,
            'global_user_id' => GlobalUser::create(['name' => 'Foreign', 'email' => 'foreign@example.test', 'status' => 'active'])->id,
            'display_name' => 'Foreign',
            'status' => RoomUserStatus::Removed,
        ]);
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.room-users.restore', [$room->slug, $foreign->id]))
            ->assertNotFound();
    }
}
