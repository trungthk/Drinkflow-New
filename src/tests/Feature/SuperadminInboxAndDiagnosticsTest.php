<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SocketHealthReason;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Superadmin;
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

    private function admin(string $email): Admin
    {
        return Admin::create([
            'name' => 'Admin '.$email, 'email' => $email,
            'password' => 'password123', 'status' => 'active',
        ]);
    }

    private function superadmin(string $email): Superadmin
    {
        return $this->createSuperadmin(['name' => 'Root '.$email, 'email' => $email, 'password' => 'password123', 'status' => 'active']);
    }

    private function notificationFor(Admin $admin, string $title, bool $read = false): AdminNotification
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
        $root = $this->superadmin('root-mail@drinkflow.test');

        $this->actingAs($root, 'superadmin')
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
        $root = $this->superadmin('root-mail2@drinkflow.test');

        $this->actingAs($root, 'superadmin')
            ->postJson(route('superadmin.system.mail-test'), ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_superadmin_inbox_never_lists_admin_notifications(): void
    {
        $root = $this->superadmin('root-inbox@drinkflow.test');
        // Same numeric ID as the superadmin, but in the `admins` table: never the superadmin's inbox.
        $other = $this->admin('other-admin@drinkflow.test');
        $this->assertSame($root->id, $other->id);
        $this->notificationFor($other, 'Someone else');

        $this->actingAs($root, 'superadmin')
            ->get(route('superadmin.notifications.page'))
            ->assertOk()
            ->assertDontSee('Someone else');
    }

    public function test_superadmin_cannot_mark_admin_notifications_as_read(): void
    {
        $root = $this->superadmin('root-read@drinkflow.test');
        $other = $this->admin('other-read@drinkflow.test');
        $theirs = $this->notificationFor($other, 'Theirs');

        $this->actingAs($root, 'superadmin')
            ->patchJson(route('superadmin.admin-notifications.read', $theirs))
            ->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);

        $this->actingAs($root, 'superadmin')
            ->postJson(route('superadmin.admin-notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('data.marked_count', 0);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_header_bell_renders_without_admin_notifications(): void
    {
        $root = $this->superadmin('root-bell@drinkflow.test');
        $this->notificationFor($this->admin('bell-admin@drinkflow.test'), 'Bell item');

        $this->actingAs($root, 'superadmin')
            ->get(route('superadmin.system.page'))
            ->assertOk()
            ->assertSee('data-sa-notifications', false)
            ->assertDontSee('Bell item');
    }
}
