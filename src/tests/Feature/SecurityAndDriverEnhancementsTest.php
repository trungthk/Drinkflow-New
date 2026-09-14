<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Events\CampaignClosed;
use App\Events\CampaignCreated;
use App\Events\OrderCreated;
use App\Events\OrderDeleted;
use App\Events\OrderUpdated;
use App\Listeners\CreateOrderNotification;
use App\Listeners\CreateOrderStatusNotification;
use App\Listeners\NotifyCampaignClosed;
use App\Listeners\NotifyCampaignCreated;
use App\Listeners\NotifyOrderDeleted;
use App\Listeners\PublishRealtimeEvent;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Traits\HandlesDatabaseDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SecurityAndDriverEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_handles_database_driver_trait_detects_active_driver_and_like_operator(): void
    {
        $tester = new class {
            use HandlesDatabaseDriver;
        };

        $driver = $tester->getDatabaseDriver();
        $this->assertNotEmpty($driver);

        if ($driver === 'pgsql') {
            $this->assertTrue($tester->isPostgreSql());
            $this->assertFalse($tester->isMySql());
            $this->assertSame('ilike', $tester->getCaseInsensitiveLikeOperator());
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            $this->assertTrue($tester->isMySql());
            $this->assertFalse($tester->isPostgreSql());
            $this->assertSame('like', $tester->getCaseInsensitiveLikeOperator());
        } elseif ($driver === 'sqlite') {
            $this->assertTrue($tester->isSqlite());
            $this->assertSame('like', $tester->getCaseInsensitiveLikeOperator());
        }
    }

    public function test_admin_login_bypasses_captcha_in_local_environment(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Local Admin',
            'email' => 'localadmin@example.test',
            'password' => 'secret123',
            'role' => AdminRole::Admin,
            'status' => AdminStatus::Active,
        ]);

        $this->app->detectEnvironment(fn () => 'local');
        Config::set('app.env', 'local');
        Config::set('captcha.disable', false);

        $response = $this->post('/admin/login', [
            'email' => 'localadmin@example.test',
            'password' => 'secret123',
            // No captcha submitted
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_login_requires_captcha_in_production_environment(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Prod Admin',
            'email' => 'prodadmin@example.test',
            'password' => 'secret123',
            'role' => AdminRole::Admin,
            'status' => AdminStatus::Active,
        ]);

        $this->app->detectEnvironment(fn () => 'production');
        Config::set('app.env', 'production');
        Config::set('captcha.disable', false);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'prodadmin@example.test',
            'password' => 'secret123',
            'captcha' => '',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['captcha']);
    }

    public function test_input_sanitization_middleware_neutralizes_xss_scripts(): void
    {
        Config::set('captcha.disable', true);

        $user = GlobalUser::create([
            'name' => 'Security Tester',
            'normalized_name' => 'SECURITY TESTER',
            'email' => 'security@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->post('/me/feedback', [
            'rating' => 5,
            'subsystem' => 'all',
            'content' => 'Test feedback <script>alert("xss")</script><img src=x onerror="alert(1)"> and safe text',
            'captcha' => '1234',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('feedbacks', [
            'global_user_id' => $user->id,
            'rating' => 5,
        ]);

        $feedback = \App\Models\Feedback::where('global_user_id', $user->id)->first();
        $this->assertNotNull($feedback);
        $this->assertStringNotContainsString('<script>', $feedback->content);
        $this->assertStringNotContainsString('alert("xss")', $feedback->content);
        $this->assertStringNotContainsString('onerror=', $feedback->content);
        $this->assertStringContainsString('and safe text', $feedback->content);
    }

    public function test_all_heavy_realtime_and_notification_listeners_implement_should_queue(): void
    {
        $listeners = [
            PublishRealtimeEvent::class,
            NotifyCampaignCreated::class,
            NotifyCampaignClosed::class,
            NotifyOrderDeleted::class,
        ];

        foreach ($listeners as $listenerClass) {
            $this->assertTrue(
                is_subclass_of($listenerClass, ShouldQueue::class),
                "Listener {$listenerClass} must implement ShouldQueue."
            );
        }
    }

    public function test_admin_auth_and_recovery_endpoints_are_rate_limited_by_email_and_ip(): void
    {
        $testIp = '198.51.100.42';
        $email = 'spamtarget@example.test';

        // 5 requests within limit
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $testIp])
                ->post('/admin/forgot-password', ['email' => $email]);
        }

        // 6th request from same IP + email is throttled with 429
        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->post('/admin/forgot-password', ['email' => $email]);

        $response->assertStatus(429);
    }

    public function test_user_auth_endpoints_are_rate_limited(): void
    {
        $testIp = '198.51.100.88';

        // Repeated hits to /logout from same IP
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $testIp])
                ->get('/logout');
        }

        // 21st request is throttled with 429
        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get('/logout');

        $response->assertStatus(429);
    }
}
