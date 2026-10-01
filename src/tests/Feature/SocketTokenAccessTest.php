<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SocketTokenAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Opening a socket-token URL directly in the browser returns 404; the app's JSON fetch still works. */
    public function test_admin_socket_token_is_hidden_from_direct_browser_access(): void
    {
        $admin = Admin::create(['name' => 'Token Admin', 'email' => 'token-admin@example.test', 'password' => Hash::make('secret'), 'status' => 'active']);
        $room = Room::create(['name' => 'Test', 'slug' => 'test', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $this->actingAs($admin, 'admin')->get('/admin/test/socket-token')->assertNotFound();
        $this->actingAs($admin, 'admin')->getJson('/admin/test/socket-token')->assertOk()->assertJsonStructure(['data' => ['token', 'channels']]);
    }

    /** The global user socket token follows the same rule. */
    public function test_user_socket_token_is_hidden_from_direct_browser_access(): void
    {
        $user = GlobalUser::create(['name' => 'Token User', 'email' => 'token-user@example.test', 'status' => 'active']);

        $this->actingAs($user, 'web')->get(route('user.me.socket-token'))->assertNotFound();
        $this->actingAs($user, 'web')->getJson(route('user.me.socket-token'))->assertOk();
    }
}
