<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Enums\SuperadminStatus;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Room;
use App\Models\SecurityEvent;
use App\Models\Superadmin;
use App\Services\Realtime\SocketTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T02: `admin` guard for /admin/*, `superadmin` guard for /superadmin/*, with no cross-guard access.
 */
class SeparateAuthGuardsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'CorrectPassword123!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config()->set('captcha.disable', true);
    }

    private function superadmin(array $attributes = []): Superadmin
    {
        return $this->createSuperadmin($attributes + [
            'name' => 'Platform Root',
            'email' => 'root-guard@drinkflow.test',
            'password' => self::PASSWORD,
            'status' => SuperadminStatus::Active,
        ]);
    }

    private function admin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Agent',
            'email' => 'agent-guard@drinkflow.test',
            'password' => self::PASSWORD,
            'status' => AdminStatus::Active,
        ]);
        $admin->rooms()->attach(Room::create(['name' => 'Guard Room', 'slug' => 'guard-room', 'status' => 'active']));

        return $admin;
    }

    public function test_superadmin_signs_in_on_its_own_guard(): void
    {
        $superadmin = $this->superadmin();

        $this->get(route('superadmin.login.page'))->assertOk()->assertSee(route('superadmin.login'), false);
        $this->post(route('superadmin.login'), ['email' => $superadmin->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('superadmin.dashboard'));

        $this->assertAuthenticatedAs($superadmin, 'superadmin');
        $this->assertGuest('admin');
        $this->assertNotNull($superadmin->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'superadmin.logged_in', 'actor_type' => 'superadmin', 'actor_id' => $superadmin->id]);
        $this->get(route('superadmin.dashboard'))->assertOk();

        $this->post(route('superadmin.logout'))->assertRedirect(route('superadmin.login.page'));
        $this->assertGuest('superadmin');
    }

    public function test_superadmin_sign_in_rejects_bad_password_suspended_and_two_factor_accounts(): void
    {
        $this->superadmin();
        $this->superadmin(['email' => 'suspended-guard@drinkflow.test', 'status' => SuperadminStatus::Suspended]);
        $this->superadmin(['email' => 'twofa-guard@drinkflow.test', 'two_factor_enabled' => true]);

        $this->post(route('superadmin.login'), ['email' => 'root-guard@drinkflow.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertTrue(SecurityEvent::query()->where('type', 'failed_login')->exists());
        $this->post(route('superadmin.login'), ['email' => 'suspended-guard@drinkflow.test', 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->post(route('superadmin.login'), ['email' => 'twofa-guard@drinkflow.test', 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => __('superadmin.auth.two_factor_unavailable')]);

        $this->assertGuest('superadmin');
    }

    public function test_credentials_only_work_on_their_own_login_form(): void
    {
        $superadmin = $this->superadmin();
        $admin = $this->admin();

        $this->post(route('admin.login'), ['email' => $superadmin->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest('admin');

        $this->post(route('superadmin.login'), ['email' => $admin->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest('superadmin');
    }

    public function test_login_only_returns_to_an_intended_url_in_its_own_area(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();

        // A guest bounced from /superadmin who then signs in as an Admin lands in the admin area.
        $this->get('/superadmin/admins/page')->assertRedirect(route('superadmin.login.page'));
        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.landing'));
        $this->post(route('admin.logout'));

        $this->get('/admin/profile')->assertRedirect(route('admin.login.page'));
        $this->post(route('superadmin.login'), ['email' => $superadmin->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('superadmin.dashboard'));
        $this->post(route('superadmin.logout'));

        $this->get('/superadmin/admins/page');
        $this->post(route('superadmin.login'), ['email' => $superadmin->email, 'password' => self::PASSWORD])
            ->assertRedirect(url('/superadmin/admins/page'));
    }

    public function test_admin_session_has_no_access_to_superadmin_area(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/superadmin')->assertRedirect(route('superadmin.login.page'));
        $this->getJson('/superadmin/admins')->assertUnauthorized();
        $this->putJson('/superadmin/system/settings', ['settings' => [['key' => 'x', 'value' => '1', 'type' => 'string']]])->assertUnauthorized();
        $this->assertDatabaseMissing('system_settings', ['key' => 'x']);
    }

    public function test_superadmin_session_has_no_access_to_admin_area(): void
    {
        $this->admin();
        $this->actingAs($this->superadmin(), 'superadmin');

        $this->get('/admin/profile')->assertRedirect(route('admin.login.page'));
        $this->get('/admin/guard-room/dashboard')->assertRedirect(route('admin.login.page'));
        $this->getJson('/admin/profile')->assertUnauthorized();
        $this->assertGuest('admin');
    }

    public function test_superadmin_actions_are_attributed_to_the_superadmin_table(): void
    {
        $admin = $this->admin();
        // Same numeric ID in both tables: attribution must not point to the Admin.
        $superadmin = $this->superadmin();
        $this->assertSame($admin->id, $superadmin->id);
        $this->actingAs($superadmin, 'superadmin');

        $this->putJson('/superadmin/system/settings', ['settings' => [['key' => 'orders.allow_cash', 'value' => true, 'type' => 'boolean']]])->assertOk();
        $this->assertDatabaseHas('system_settings', ['key' => 'orders.allow_cash', 'updated_by_superadmin_id' => $superadmin->id, 'updated_by_admin_id' => null]);

        $this->postJson('/superadmin/versions', ['version' => 'v9.9.9', 'title' => 'Guard release', 'release_date' => '2026-09-30'])->assertCreated();
        $this->assertDatabaseHas('versions', ['version' => 'v9.9.9', 'created_by_superadmin_id' => $superadmin->id, 'created_by_admin_id' => null]);

        $log = AuditLog::query()->where('event', 'version.created')->sole();
        $this->assertSame(AuditLog::ACTOR_SUPERADMIN, $log->actor_type);
        $this->assertSame($superadmin->id, $log->actor_id);
        $this->assertSame(0, $log->admins()->count());
    }

    public function test_superadmin_socket_token_carries_the_superadmin_actor(): void
    {
        $superadmin = $this->superadmin();
        // Pin the signing secret so the check does not depend on SOCKET_TOKEN_SECRET in the local .env.
        config(['services.realtime.socket_token_secret' => 'test-socket-secret']);

        $token = $this->actingAs($superadmin, 'superadmin')->getJson(route('superadmin.socket-token'))->assertOk()->json('data.token');
        [$encoded, $signature] = explode('.', $token, 2);
        $this->assertTrue(hash_equals(hash_hmac('sha256', $encoded, 'test-socket-secret'), $signature));
        $claims = json_decode((string) base64_decode(strtr($encoded, '-_', '+/')), true);

        $this->assertSame('superadmin', $claims['actor_type']);
        $this->assertSame($superadmin->id, $claims['superadmin_id']);
        $this->assertArrayNotHasKey('admin_id', $claims);
    }
}
