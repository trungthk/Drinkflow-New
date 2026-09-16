<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationChannelGuideTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure every supported platform has an integration guide on the page.
     *
     * @return void
     */
    public function test_notification_channel_page_renders_platform_guides(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Integration Admin',
            'email' => 'integration-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Công Nghệ',
            'slug' => 'cong-nghe',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.notification-channels.page', $room))
            ->assertOk()
            ->assertSeeText(__('admin.integration_guide_title'))
            ->assertSee('data-platform-guide="telegram"', false)
            ->assertSee('data-platform-guide="slack"', false)
            ->assertSee('data-platform-guide="chatwork"', false)
            ->assertSee('data-platform-guide="webhook"', false)
            ->assertSeeText(__('admin.integration_guide_telegram_step_1'))
            ->assertSeeText(__('admin.integration_guide_slack_step_1'))
            ->assertSeeText(__('admin.integration_guide_chatwork_step_1'))
            ->assertSeeText(__('admin.integration_guide_webhook_step_1'));
    }
}
