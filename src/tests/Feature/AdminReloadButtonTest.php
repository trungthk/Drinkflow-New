<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReloadButtonTest extends TestCase
{
    use RefreshDatabase;

    /** Verify the reload button sits next to the filter controls on every list page that has filters. */
    public function test_list_pages_render_reload_button(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'reload-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Reload Room', 'slug' => 'reload-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        foreach (['admin.campaigns.page', 'admin.orders.page', 'admin.debts.page', 'admin.audit.page', 'admin.room-users.page'] as $route) {
            $this->actingAs($admin, 'admin')
                ->get(route($route, $room->slug))
                ->assertOk()
                ->assertSee('data-reload-page', false)
                ->assertSee(__('admin.reload'));
        }
    }
}
