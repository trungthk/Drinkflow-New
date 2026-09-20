<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Room;
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
}
