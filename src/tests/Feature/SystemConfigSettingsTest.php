<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\SystemSetting;
use App\Services\System\SystemConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SystemConfigSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): AdminAccount
    {
        return AdminAccount::create([
            'name' => 'Root', 'email' => 'root-config@drinkflow.test',
            'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active',
        ]);
    }

    public function test_mail_settings_override_env_and_empty_fields_fall_back_to_env(): void
    {
        $envHost = config('mail.mailers.smtp.host');
        $envMailer = config('mail.default');
        $root = $this->superadmin();

        $this->actingAs($root, 'admin')->putJson('/superadmin/system/mail', [
            'mailer' => 'smtp',
            'host' => 'smtp.example.test',
            'port' => 587,
            'scheme' => 'smtp',
            'username' => 'mailer-user',
            'password' => 'smtp-secret',
            'from_address' => 'noreply@example.test',
            'from_name' => 'DrinkFlow Mailer',
        ])->assertOk()
            ->assertJsonPath('data.fields.host.source', 'system')
            ->assertJsonPath('data.fields.host.value', 'smtp.example.test')
            ->assertJsonPath('data.fields.password.configured', true)
            ->assertJsonPath('data.fields.password.value', null)
            ->assertJsonPath('data.health.driver', 'smtp');

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('smtp-secret', config('mail.mailers.smtp.password'));
        $this->assertSame('noreply@example.test', config('mail.from.address'));

        // The password is stored encrypted and never returned by the settings API.
        $stored = SystemSetting::where('key', 'mail.password')->firstOrFail();
        $this->assertTrue($stored->is_secret);
        $this->assertNotSame('smtp-secret', $stored->value);
        $this->actingAs($root, 'admin')->getJson('/superadmin/system')
            ->assertOk()
            ->assertJsonPath('data.mail_config.password.value', null)
            ->assertDontSee('smtp-secret');

        // Emptying host/mailer falls back to .env; an empty password keeps the saved one.
        $this->actingAs($root, 'admin')->putJson('/superadmin/system/mail', [
            'mailer' => null,
            'host' => null,
            'port' => 587,
            'password' => null,
        ])->assertOk()
            ->assertJsonPath('data.fields.host.source', 'env')
            ->assertJsonPath('data.fields.password.source', 'system');

        $this->assertSame($envHost, config('mail.mailers.smtp.host'));
        $this->assertSame($envMailer, config('mail.default'));
        $this->assertSame('smtp-secret', config('mail.mailers.smtp.password'));
        $this->assertDatabaseMissing('system_settings', ['key' => 'mail.host']);

        // clear_password removes the saved password.
        $this->actingAs($root, 'admin')->putJson('/superadmin/system/mail', ['clear_password' => true])
            ->assertOk()
            ->assertJsonPath('data.fields.password.source', 'env');
        $this->assertDatabaseMissing('system_settings', ['key' => 'mail.password']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'system.mail_config_updated']);
    }

    public function test_mail_settings_are_validated(): void
    {
        $this->actingAs($this->superadmin(), 'admin')->putJson('/superadmin/system/mail', [
            'mailer' => 'postmark',
            'port' => 70000,
            'scheme' => 'tls',
            'from_address' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['mailer', 'port', 'scheme', 'from_address']);
    }

    public function test_saved_overrides_are_applied_on_boot(): void
    {
        $service = app(SystemConfigService::class);
        $service->save(SystemConfigService::GROUP_MAIL, ['host' => 'boot.example.test'], null);
        $service->save(SystemConfigService::GROUP_STORAGE, ['quota_mb' => 2048], null);

        // Simulate the next request: config comes back from .env, then the provider applies overrides.
        Config::set('mail.mailers.smtp.host', '127.0.0.1');
        Config::set('filesystems.quota_mb', null);
        \App\Services\System\SystemSettingsService::clearCache();
        $service->apply();

        $this->assertSame('boot.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(2048, config('filesystems.quota_mb'));
    }

    public function test_storage_settings_override_disk_and_quota(): void
    {
        $root = $this->superadmin();

        $this->actingAs($root, 'admin')->putJson('/superadmin/system/storage', [
            'disk' => 'public',
            'quota_mb' => 1024,
        ])->assertOk()
            ->assertJsonPath('data.fields.disk.source', 'system')
            ->assertJsonPath('data.fields.quota_mb.value', 1024)
            ->assertJsonPath('data.health.disk', 'public')
            ->assertJsonPath('data.health.limit', 'quota')
            ->assertJsonPath('data.health.total_bytes', 1024 * 1024 * 1024);

        $this->assertSame('public', config('filesystems.default'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'system.storage_config_updated']);

        // Only local disks can be selected (no S3 adapter is installed).
        $this->actingAs($root, 'admin')->putJson('/superadmin/system/storage', ['disk' => 's3'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['disk']);
        $this->actingAs($root, 'admin')->putJson('/superadmin/system/storage', ['quota_mb' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quota_mb']);
    }

    public function test_generic_settings_endpoint_cannot_write_mail_or_storage_keys(): void
    {
        $this->actingAs($this->superadmin(), 'admin')->putJson('/superadmin/system/settings', [
            'settings' => [['key' => 'mail.password', 'value' => 'plain-text']],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('system_settings', ['key' => 'mail.password']);
    }

    public function test_room_admin_cannot_change_mail_or_storage_settings(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Operator', 'email' => 'operator-config@drinkflow.test',
            'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')->putJson('/superadmin/system/mail', ['host' => 'evil.test'])->assertForbidden();
        $this->actingAs($admin, 'admin')->putJson('/superadmin/system/storage', ['quota_mb' => 1])->assertForbidden();
        $this->assertDatabaseMissing('system_settings', ['key' => 'mail.host']);
    }

    public function test_system_page_renders_mail_and_storage_forms(): void
    {
        $this->actingAs($this->superadmin(), 'admin')->get('/superadmin/system/page')
            ->assertOk()
            ->assertSee('id="mail-config-form"', false)
            ->assertSee('id="storage-config-form"', false)
            ->assertSee(__('superadmin.system.mail_config_title'))
            ->assertSee(__('superadmin.system.storage_config_title'));
    }
}
