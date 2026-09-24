<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Services\System\MailHealthService;
use App\Services\System\StorageHealthService;
use App\Services\System\SupervisorHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): AdminAccount
    {
        return AdminAccount::create([
            'name' => 'Root', 'email' => 'root-health@drinkflow.test',
            'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active',
        ]);
    }

    public function test_storage_health_check_round_trips_a_probe_file(): void
    {
        $result = app(StorageHealthService::class)->check();

        $this->assertSame('ok', $result['status']);
        $this->assertSame(config('filesystems.default'), $result['disk']);
        $this->assertSame(config('filesystems.disks.'.config('filesystems.default').'.driver'), $result['driver']);
    }

    public function test_storage_health_check_reports_capacity_of_a_local_disk(): void
    {
        $result = app(StorageHealthService::class)->check();

        $this->assertIsInt($result['total_bytes']);
        $this->assertGreaterThan(0, $result['total_bytes']);
        $this->assertSame($result['total_bytes'], $result['used_bytes'] + $result['free_bytes']);
    }

    public function test_dashboard_health_rows_link_to_their_detail_pages(): void
    {
        $this->actingAs($this->superadmin(), 'admin')
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('<a class="sa-health-row" href="'.route('superadmin.queue.page').'">', false)
            ->assertSee('<a class="sa-health-row" href="'.route('superadmin.socket.page').'">', false)
            ->assertSee('<a class="sa-health-row" href="'.route('superadmin.system.page').'">', false)
            ->assertSee('id="storage-usage"', false);
    }

    public function test_mail_health_check_reports_not_configured_for_the_log_driver(): void
    {
        Config::set('mail.default', 'log');

        $result = app(MailHealthService::class)->check();

        $this->assertSame('log', $result['driver']);
        $this->assertFalse($result['configured']);
    }

    public function test_supervisor_health_check_is_hidden_when_not_enabled(): void
    {
        Config::set('services.supervisor.enabled', false);

        $this->assertNull(app(SupervisorHealthService::class)->check());
    }

    public function test_supervisor_health_check_is_hidden_on_local_even_when_enabled(): void
    {
        Config::set('services.supervisor.enabled', true);
        Config::set('services.supervisor.program', 'drinkflow-queue:*');
        $this->app->detectEnvironment(fn () => 'local');

        $this->assertNull(app(SupervisorHealthService::class)->check());
    }

    public function test_dashboard_json_reports_the_full_health_snapshot(): void
    {
        $root = $this->superadmin();

        $response = $this->actingAs($root, 'admin')->getJson('/superadmin');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['system_health' => ['database', 'queue', 'socket', 'mail', 'storage']]])
            ->assertJsonPath('data.system_health.database.status', 'ok');
    }

    public function test_superadmin_can_trigger_a_real_test_email_without_sending_over_the_network(): void
    {
        Mail::fake();
        Config::set('mail.default', 'log');
        $root = $this->superadmin();

        // The recipient is now typed in the "send test email" modal (SendTestMailRequest).
        $this->actingAs($root, 'admin')->postJson('/superadmin/system/mail/test', ['email' => $root->email])
            ->assertOk()
            ->assertJsonPath('data.sent', true);
    }
}
