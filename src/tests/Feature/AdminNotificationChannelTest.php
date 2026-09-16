<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Room;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\RoomNotificationChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminNotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccount $admin;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminAccount::create([
            'name' => 'Channel Tester',
            'email' => 'channel-tester@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);

        $this->room = Room::create([
            'name' => 'Engineering',
            'slug' => 'engineering',
            'status' => 'active',
        ]);

        $this->admin->rooms()->attach($this->room);
    }

    /** Ensure testing a channel with default ping sends correct payload. */
    public function test_admin_can_send_test_ping_notification(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $channel = app(RoomNotificationChannelService::class)->save($this->room->id, [
            'type' => 'telegram',
            'name' => 'Team Telegram',
            'status' => 'enabled',
            'config' => ['bot_token' => '123456:abcdef', 'chat_id' => '-100987654'],
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->id}/notification-channels/{$channel->id}/test");

        $response->assertOk()
            ->assertJsonPath('message', 'test_sent')
            ->assertJsonPath('data.template', 'test_ping');

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'api.telegram.org/bot123456:abcdef/sendMessage')
                && $request['chat_id'] === '-100987654'
                && $request['parse_mode'] === 'HTML'
                && str_contains((string) $request['text'], 'DrinkFlow');
        });
    }

    /** Ensure testing channel with different templates sends formatted content. */
    public function test_admin_can_send_campaign_created_template_test(): void
    {
        Http::fake(['https://hooks.slack.com/*' => Http::response('ok', 200)]);

        $channel = app(RoomNotificationChannelService::class)->save($this->room->id, [
            'type' => 'slack',
            'name' => 'Team Slack',
            'status' => 'enabled',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/T00/B00/X00'],
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->id}/notification-channels/{$channel->id}/test", [
                'template' => 'campaign.created',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'test_sent')
            ->assertJsonPath('data.template', 'campaign.created');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://hooks.slack.com/services/T00/B00/X00'
                && str_contains((string) $request['text'], '[DrinkFlow]')
                && str_contains((string) $request['text'], 'Phê La');
        });
    }

    /** Ensure testing channel with debt reminder template sends formatted content. */
    public function test_admin_can_send_debt_reminder_template_test(): void
    {
        Http::fake(['https://api.chatwork.com/*' => Http::response(['message_id' => '123'], 200)]);

        $channel = app(RoomNotificationChannelService::class)->save($this->room->id, [
            'type' => 'chatwork',
            'name' => 'Team ChatWork',
            'status' => 'enabled',
            'config' => ['api_token' => 'chatwork-secret-token', 'room_id' => '98765432'],
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->id}/notification-channels/{$channel->id}/test", [
                'template' => 'debt.reminder',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'test_sent')
            ->assertJsonPath('data.template', 'debt.reminder');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.chatwork.com/v2/rooms/98765432/messages'
                && str_contains((string) $request['body'], '[info][title]')
                && str_contains((string) $request['body'], '[/info]');
        });
    }

    /** Ensure testing channel is rate limited to 5 attempts per minute. */
    public function test_channel_testing_is_rate_limited(): void
    {
        Http::fake(['https://hooks.slack.com/*' => Http::response('ok', 200)]);
        RateLimiter::clear("channel-test:1:{$this->admin->id}");

        $channel = app(RoomNotificationChannelService::class)->save($this->room->id, [
            'type' => 'slack',
            'name' => 'Rate Limit Slack',
            'status' => 'enabled',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/T00/B00/X00'],
        ]);

        // Send 5 test notifications (all should succeed)
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->admin, 'admin')
                ->postJson("/admin/{$this->room->id}/notification-channels/{$channel->id}/test", ['template' => 'test_ping'])
                ->assertOk();
        }

        // 6th test notification should hit rate limit (429)
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->id}/notification-channels/{$channel->id}/test", ['template' => 'test_ping']);

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    /** Ensure RoomNotificationChannelDispatcher formats rich messages accurately across all drivers. */
    public function test_dispatcher_formats_messages_for_all_supported_drivers(): void
    {
        $dispatcher = app(RoomNotificationChannelDispatcher::class);
        $payload = [
            'event' => 'campaign.created',
            'title' => 'Chiến dịch mới',
            'message' => "Chiến dịch mới\nTên: Trà sữa Phê La\nQuán / Thương hiệu: Phê La\nThời hạn: 11:30 16/09/2026\nĐặt món: https://drinkflow.test/order",
        ];

        // Telegram HTML
        $telegramText = $dispatcher->formatTelegramMessage($payload);
        $this->assertStringContainsString('<b>🚀 [DrinkFlow] Chiến dịch mới</b>', $telegramText);
        $this->assertStringContainsString('<b>Tên:</b> Trà sữa Phê La', $telegramText);
        $this->assertStringContainsString('<a href="https://drinkflow.test/order">', $telegramText);

        // Slack mrkdwn
        $slackText = $dispatcher->formatSlackMessage($payload);
        $this->assertStringContainsString('*🚀 [DrinkFlow] Chiến dịch mới*', $slackText);
        $this->assertStringContainsString('*Tên:* Trà sữa Phê La', $slackText);
        $this->assertStringContainsString('<https://drinkflow.test/order|', $slackText);

        // Chatwork BBCode
        $chatworkText = $dispatcher->formatChatworkMessage($payload);
        $this->assertStringContainsString('[info][title]🚀 [DrinkFlow] Chiến dịch mới[/title]', $chatworkText);
        $this->assertStringContainsString('Tên: Trà sữa Phê La', $chatworkText);
        $this->assertStringContainsString('👉 ', $chatworkText);
        $this->assertStringContainsString('[/info]', $chatworkText);

        // Webhook JSON
        $webhookData = $dispatcher->formatWebhookPayload($payload);
        $this->assertSame('campaign.created', $webhookData['event']);
        $this->assertSame('🚀', $webhookData['icon']);
        $this->assertArrayHasKey('timestamp', $webhookData);
    }
}
