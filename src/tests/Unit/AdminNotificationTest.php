<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin notification automatically fills title and body when missing.
     */
    public function test_admin_notification_auto_generates_title_and_body_when_missing(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@company.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $room = Room::create([
            'name' => 'Test Admin Room',
            'slug' => 'test-admin-room',
            'status' => 'active',
        ]);

        $notif1 = AdminNotification::create([
            'admin_id' => $admin->id,
            'room_id' => $room->id,
            'type' => 'order.price_adjusted',
            'data' => ['order_id' => 88, 'order_code' => 'ORD-20260916-PRC1', 'reason' => 'Đổi topping'],
        ]);

        $notif2 = AdminNotification::create([
            'admin_id' => $admin->id,
            'room_id' => $room->id,
            'type' => 'campaign.created',
        ]);

        $this->assertNotEmpty($notif1->fresh()->title);
        $this->assertNotEmpty($notif1->fresh()->body);
        $this->assertStringContainsString('ORD-20260916-PRC1', $notif1->fresh()->body);
        $this->assertStringContainsString('Đổi topping', $notif1->fresh()->body);

        $this->assertNotEmpty($notif2->fresh()->title);
        $this->assertNotEmpty($notif2->fresh()->body);
        $this->assertStringContainsString('Chiến dịch đặt món mới', $notif2->fresh()->body);
    }
}
