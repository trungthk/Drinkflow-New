<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Services\System\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSettingsService::clearCache();
    }

    protected function tearDown(): void
    {
        SystemSettingsService::clearCache();
        parent::tearDown();
    }

    public function test_active_maintenance_shows_maintenance_page_to_guests_users_and_room_admins(): void
    {
        $this->setMaintenance(true, null, now()->addHour()->format('Y-m-d\TH:i'));

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertSee(__('errors.maintenance.title'));

        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'user-maint@drinkflow.test', 'status' => 'active']);
        $this->actingAs($user, 'web')->get('/')->assertStatus(503);

        $roomAdmin = AdminAccount::create(['name' => 'Room admin', 'email' => 'room-admin-maint@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);
        $this->actingAs($roomAdmin, 'admin')->get('/admin/profile')
            ->assertStatus(503)
            ->assertSee(__('errors.maintenance.title'));
        $this->actingAs($roomAdmin, 'admin')->getJson('/admin/profile')
            ->assertStatus(503)
            ->assertJsonPath('message', __('errors.maintenance.json_message'));
    }

    public function test_sign_in_routes_stay_available_and_superadmin_sees_banner(): void
    {
        $this->setMaintenance(true);

        $this->get('/admin/login')->assertOk();

        $root = AdminAccount::create(['name' => 'Root', 'email' => 'root-maint@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);
        $this->actingAs($root, 'admin')->get('/superadmin')
            ->assertOk()
            ->assertSee('data-maintenance-banner="active"', false)
            ->assertSee(__('superadmin.maintenance_banner.active'));

        // The superadmin session must not unlock public/user pages sharing the same browser session.
        $this->actingAs($root, 'admin')->get('/')
            ->assertStatus(503)
            ->assertSee(__('errors.maintenance.title'));
    }

    public function test_scheduled_or_expired_maintenance_does_not_block_and_banner_reflects_schedule(): void
    {
        $root = AdminAccount::create(['name' => 'Root', 'email' => 'root-sched@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);

        $this->setMaintenance(true, now()->addDay()->format('Y-m-d\TH:i'));
        $this->get('/')->assertOk();
        $this->actingAs($root, 'admin')->get('/superadmin')->assertOk()->assertSee('data-maintenance-banner="scheduled"', false);

        $this->setMaintenance(true, now()->subDays(2)->format('Y-m-d\TH:i'), now()->subDay()->format('Y-m-d\TH:i'));
        $this->get('/')->assertOk();

        $this->setMaintenance(false);
        $this->actingAs($root, 'admin')->get('/superadmin')->assertOk()->assertDontSee('data-maintenance-banner', false);
    }

    private function setMaintenance(bool $enabled, ?string $startsAt = null, ?string $endsAt = null): void
    {
        $service = app(SystemSettingsService::class);
        $service->set('maintenance.enabled', $enabled, 'boolean');
        $service->set('maintenance.starts_at', $startsAt, 'string');
        $service->set('maintenance.ends_at', $endsAt, 'string');
        SystemSettingsService::clearCache();
    }
}
