<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\CrawlerPreview;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneOperationalDataTest extends TestCase
{
    use RefreshDatabase;

    /** Test configured retention periods are applied only to eligible records. */
    public function test_command_prunes_expired_operational_data(): void
    {
        config()->set('retention.audit_logs_days', 30);
        config()->set('retention.read_admin_notifications_days', 30);
        config()->set('retention.crawler_previews_days', 2);

        $admin = AdminAccount::create(['name' => 'Admin', 'email' => 'prune@example.test', 'password' => 'password', 'role' => 'admin', 'status' => 'active']);
        $room = Room::create(['name' => 'Prune Room', 'slug' => 'prune-room', 'status' => 'active']);
        $oldAudit = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'old.event', 'target_type' => 'room', 'room_id' => $room->id, 'created_at' => now()->subDays(31)]);
        $freshAudit = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'fresh.event', 'target_type' => 'room', 'room_id' => $room->id, 'created_at' => now()->subDays(29)]);
        $oldNotification = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Old', 'read_at' => now()->subDays(31)]);
        $unreadNotification = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Unread', 'created_at' => now()->subDays(90)]);
        $oldPreview = CrawlerPreview::create(['room_id' => $room->id, 'admin_id' => $admin->id, 'source_url' => 'https://example.test/old', 'items' => [], 'expires_at' => now()->subDay(), 'created_at' => now()->subDays(3)]);
        $freshPreview = CrawlerPreview::create(['room_id' => $room->id, 'admin_id' => $admin->id, 'source_url' => 'https://example.test/new', 'items' => [], 'expires_at' => now()->addDay()]);

        $this->artisan('drinkflow:prune-operational-data')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $oldAudit->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $freshAudit->id]);
        $this->assertDatabaseMissing('admin_notifications', ['id' => $oldNotification->id]);
        $this->assertDatabaseHas('admin_notifications', ['id' => $unreadNotification->id]);
        $this->assertDatabaseMissing('crawler_previews', ['id' => $oldPreview->id]);
        $this->assertDatabaseHas('crawler_previews', ['id' => $freshPreview->id]);
    }
}
