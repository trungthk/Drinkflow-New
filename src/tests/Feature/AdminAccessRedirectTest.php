<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Enums\RoomStatus;
use App\Models\AdminAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessRedirectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure guests reach admin login from protected pages including the root.
     *
     * @return void
     */
    public function test_guest_pages_redirect_to_admin_login(): void
    {
        foreach (['/admin', '/admin/profile', '/admin/example/dashboard'] as $path) {
            $this->get($path)->assertRedirect(route('admin.login.page'));
        }

        $this->getJson('/admin/profile')->assertUnauthorized();
    }

    /**
     * Ensure public admin authentication and recovery pages remain accessible.
     *
     * @return void
     */
    public function test_public_admin_pages_do_not_require_authentication(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee(__('admin.e2e_encryption'));
        $this->get('/admin/forgot-password')->assertOk();
        $this->get('/admin/verify-otp')->assertRedirect(route('admin.forgot-password.page'));
        $this->get('/admin/reset-password')->assertRedirect(route('admin.forgot-password.page'));
    }

    /**
     * Ensure a denied room visit reaches the login view without redirecting back.
     *
     * @return void
     */
    public function test_unassigned_admin_is_redirected_to_login_view(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin', 'email' => 'denied@example.test', 'password' => 'secret',
            'role' => AdminRole::Admin, 'status' => AdminStatus::Active,
        ]);
        $room = Room::create(['name' => 'Other room', 'slug' => 'other-room', 'status' => RoomStatus::Active]);

        $this->actingAs($admin, 'admin')->get('/admin/'.$room->slug.'/dashboard')
            ->assertRedirect(route('admin.login.page'));
        $this->get('/admin/login')->assertOk()->assertViewIs('admin.auth.login');
        $this->getJson('/admin/'.$room->slug.'/dashboard/data')->assertForbidden();
    }

    /**
     * Ensure disabled sessions cannot access the admin root or profile.
     *
     * @return void
     */
    public function test_inactive_admin_is_signed_out_and_redirected(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin', 'email' => 'inactive@example.test', 'password' => 'secret',
            'role' => AdminRole::Admin, 'status' => AdminStatus::Inactive,
        ]);

        foreach (['/admin', '/admin/profile'] as $path) {
            $this->actingAs($admin, 'admin')->get($path)->assertRedirect(route('admin.login.page'));
            $this->assertGuest('admin');
            $this->get('/admin/login')->assertOk();
        }
    }

    /**
     * Ensure an admin blocked while signed in loses the session on admin and superadmin routes.
     *
     * @return void
     */
    public function test_blocked_admin_session_is_signed_out_on_admin_and_superadmin_routes(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin', 'email' => 'blocked@example.test', 'password' => 'secret',
            'role' => AdminRole::Admin, 'status' => AdminStatus::Active,
        ]);
        $superadmin = AdminAccount::create([
            'name' => 'Root', 'email' => 'blocked-root@example.test', 'password' => 'secret',
            'role' => AdminRole::SuperAdmin, 'status' => AdminStatus::Active,
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/profile')->assertOk();
        $admin->update(['status' => AdminStatus::Blocked]);
        $this->get('/admin/profile')->assertRedirect(route('admin.login.page'));
        $this->assertGuest('admin');

        $this->actingAs($superadmin, 'admin')->get('/superadmin')->assertOk();
        $superadmin->update(['status' => AdminStatus::Blocked]);
        $this->get('/superadmin')->assertRedirect(route('admin.login.page'));
        $this->assertGuest('admin');

        $this->actingAs($superadmin->refresh(), 'admin')->getJson('/superadmin/admins')->assertForbidden();
    }
}
