<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\SocketHealthReason;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Services\System\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Superadmin: socket health diagnostics, test mail modal endpoint and the personal notification inbox.
 */
class SuperadminInboxAndDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email, AdminRole $role = AdminRole::SuperAdmin): AdminAccount
    {
        return AdminAccount::create([
            'name' => 'Admin '.$email, 'email' => $email,
            'password' => 'password123', 'role' => $role, 'status' => 'active',
        ]);
    }

    private function notificationFor(AdminAccount $admin, string $title, bool $read = false): AdminNotification
    {
        return AdminNotification::create([
            'admin_id' => $admin->id, 'type' => 'custom.event', 'title' => $title,
            'body' => $title.' body', 'read_at' => $read ? now() : null,
        ]);
    }

    public function test_socket_health_reports_secret_mismatch_when_gateway_answers_401(): void
    {
        Config::set('services.realtime.url', 'http://realtime.test:3001');
        Config::set('services.realtime.internal_secret', 'wrong-secret');
        Http::fake(['realtime.test:3001/health' => Http::response(['error' => 'unauthorized'], 401)]);

        $socket = app(SystemHealthService::class)->socket();

        $this->assertSame('unauthorized', $socket['status']);
        $this->assertSame(SocketHealthReason::SecretMismatch->value, $socket['reason']);
        $this->assertSame(401, $socket['http_status']);
        $this->assertStringContainsString('REALTIME_INTERNAL_SECRET', $socket['reason_message']);
    }

    public function test_socket_health_reports_connection_failure_and_missing_secret(): void
    {
        Config::set('services.realtime.url', 'http://realtime.test:3001');
        Config::set('services.realtime.internal_secret', 'secret');
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $socket = app(SystemHealthService::class)->socket();
        $this->assertSame('unreachable', $socket['status']);
        $this->assertSame(SocketHealthReason::ConnectionFailed->value, $socket['reason']);

        Config::set('services.realtime.internal_secret', '');
        $this->assertSame(SocketHealthReason::SecretMissing->value, app(SystemHealthService::class)->socket()['reason']);
    }

    public function test_socket_health_is_healthy_when_gateway_answers(): void
    {
        Config::set('services.realtime.url', 'http://realtime.test:3001');
        Config::set('services.realtime.internal_secret', 'secret');
        Http::fake(['realtime.test:3001/health' => Http::response(['connected_users' => 3])]);

        $socket = app(SystemHealthService::class)->socket();

        $this->assertSame('healthy', $socket['status']);
        $this->assertNull($socket['reason']);
        $this->assertSame(3, $socket['connected_users']);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Realtime-Secret', 'secret'));
    }

    public function test_test_mail_is_sent_to_the_typed_address_with_escaped_message(): void
    {
        Config::set('mail.default', 'array');
        $root = $this->admin('root-mail@drinkflow.test');

        $this->actingAs($root, 'admin')
            ->postJson(route('superadmin.system.mail-test'), ['email' => 'ops@example.com', 'message' => "Hello <b>team</b>\nLine 2"])
            ->assertOk()
            ->assertJsonPath('data.sent', true);

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        $this->assertSame('ops@example.com', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('Hello &lt;b&gt;team&lt;/b&gt;<br>', $email->getHtmlBody());
        $this->assertDatabaseHas('audit_logs', ['event' => 'system.mail_test']);
    }

    public function test_test_mail_requires_a_valid_email(): void
    {
        $root = $this->admin('root-mail2@drinkflow.test');

        $this->actingAs($root, 'admin')
            ->postJson(route('superadmin.system.mail-test'), ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_inbox_only_lists_notifications_of_the_signed_in_account(): void
    {
        $root = $this->admin('root-inbox@drinkflow.test');
        $other = $this->admin('other-admin@drinkflow.test', AdminRole::Admin);
        $this->notificationFor($root, 'Mine unread');
        $this->notificationFor($root, 'Mine read', true);
        $this->notificationFor($other, 'Someone else');

        $this->actingAs($root, 'admin')
            ->get(route('superadmin.notifications.page'))
            ->assertOk()
            ->assertSee('Mine unread')
            ->assertSee('Mine read')
            ->assertDontSee('Someone else');

        $this->actingAs($root, 'admin')
            ->get(route('superadmin.notifications.page', ['inbox_status' => 'unread']))
            ->assertOk()
            ->assertSee('Mine unread')
            ->assertDontSee('Mine read');
    }

    public function test_mark_read_is_limited_to_own_notifications(): void
    {
        $root = $this->admin('root-read@drinkflow.test');
        $other = $this->admin('other-read@drinkflow.test', AdminRole::Admin);
        $mine = $this->notificationFor($root, 'Mine');
        $theirs = $this->notificationFor($other, 'Theirs');

        $this->actingAs($root, 'admin')
            ->patchJson(route('superadmin.admin-notifications.read', $theirs))
            ->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);

        $this->actingAs($root, 'admin')
            ->patchJson(route('superadmin.admin-notifications.read', $mine))
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
        $this->assertNotNull($mine->fresh()->read_at);
    }

    public function test_mark_all_read_only_touches_own_notifications(): void
    {
        $root = $this->admin('root-all@drinkflow.test');
        $other = $this->admin('other-all@drinkflow.test', AdminRole::Admin);
        $this->notificationFor($root, 'A');
        $this->notificationFor($root, 'B');
        $theirs = $this->notificationFor($other, 'C');

        $this->actingAs($root, 'admin')
            ->postJson(route('superadmin.admin-notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('data.marked_count', 2);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_header_bell_shows_unread_count(): void
    {
        $root = $this->admin('root-bell@drinkflow.test');
        $this->notificationFor($root, 'Bell item');

        $this->actingAs($root, 'admin')
            ->get(route('superadmin.system.page'))
            ->assertOk()
            ->assertSee('data-sa-notifications', false)
            ->assertSee('Bell item');
    }
}
