<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\CrawlerPreview;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PruneOperationalDataTest extends TestCase
{
    use RefreshDatabase;

    /** Test configured retention periods are applied only to eligible records and log files. */
    public function test_command_prunes_expired_operational_data(): void
    {
        config()->set('retention.audit_logs_days', 30);
        config()->set('retention.read_admin_notifications_days', 30);
        config()->set('retention.read_user_notifications_days', 30);
        config()->set('retention.crawler_previews_days', 2);
        config()->set('retention.log_files_days', 14);

        $admin = AdminAccount::create(['name' => 'Admin', 'email' => 'prune@example.test', 'password' => 'password', 'role' => 'admin', 'status' => 'active']);
        $globalUser = GlobalUser::create(['name' => 'Prune User', 'email' => 'prune-user@example.test', 'status' => 'active']);
        $room = Room::create(['name' => 'Prune Room', 'slug' => 'prune-room', 'status' => 'active']);

        // 1. Audit Logs & Admin Audit Logs
        $oldAudit = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'old.event', 'target_type' => 'room', 'room_id' => $room->id, 'created_at' => now()->subDays(31)]);
        $freshAudit = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'fresh.event', 'target_type' => 'room', 'room_id' => $room->id, 'created_at' => now()->subDays(29)]);

        DB::table('admin_audit_logs')->insert([
            ['admin_id' => $admin->id, 'audit_log_id' => $oldAudit->id],
            ['admin_id' => $admin->id, 'audit_log_id' => $freshAudit->id],
        ]);

        // 2. Admin Notifications
        $oldAdminNotification = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Old Admin', 'read_at' => now()->subDays(31)]);
        $unreadAdminNotification = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Unread Admin', 'created_at' => now()->subDays(90)]);

        // 3. User Notifications
        $oldUserNotification = UserNotification::create(['global_user_id' => $globalUser->id, 'type' => 'order.created', 'title' => 'Old User', 'read_at' => now()->subDays(31)]);
        $unreadUserNotification = UserNotification::create(['global_user_id' => $globalUser->id, 'type' => 'order.created', 'title' => 'Unread User', 'created_at' => now()->subDays(90)]);

        // 4. Crawler Previews
        $oldPreview = CrawlerPreview::create(['room_id' => $room->id, 'admin_id' => $admin->id, 'source_url' => 'https://example.test/old', 'items' => [], 'expires_at' => now()->subDay(), 'created_at' => now()->subDays(3)]);
        $freshPreview = CrawlerPreview::create(['room_id' => $room->id, 'admin_id' => $admin->id, 'source_url' => 'https://example.test/new', 'items' => [], 'expires_at' => now()->addDay()]);

        // 5. Temporary Log Files
        $logsDir = storage_path('logs');
        $oldLogFile = $logsDir . '/test-old-prune.log';
        $freshLogFile = $logsDir . '/test-fresh-prune.log';
        File::put($oldLogFile, 'Old log content');
        File::put($freshLogFile, 'Fresh log content');
        touch($oldLogFile, now()->subDays(15)->timestamp);
        touch($freshLogFile, now()->subDays(2)->timestamp);

        try {
            $this->artisan('drinkflow:prune-operational-data')->assertSuccessful();

            // Assert Audit logs and admin_audit_logs cascade
            $this->assertDatabaseMissing('audit_logs', ['id' => $oldAudit->id]);
            $this->assertDatabaseMissing('admin_audit_logs', ['audit_log_id' => $oldAudit->id]);
            $this->assertDatabaseHas('audit_logs', ['id' => $freshAudit->id]);
            $this->assertDatabaseHas('admin_audit_logs', ['audit_log_id' => $freshAudit->id]);

            // Assert Admin Notifications
            $this->assertDatabaseMissing('admin_notifications', ['id' => $oldAdminNotification->id]);
            $this->assertDatabaseHas('admin_notifications', ['id' => $unreadAdminNotification->id]);

            // Assert User Notifications
            $this->assertDatabaseMissing('user_notifications', ['id' => $oldUserNotification->id]);
            $this->assertDatabaseHas('user_notifications', ['id' => $unreadUserNotification->id]);

            // Assert Crawler Previews
            $this->assertDatabaseMissing('crawler_previews', ['id' => $oldPreview->id]);
            $this->assertDatabaseHas('crawler_previews', ['id' => $freshPreview->id]);

            // Assert Log Files
            $this->assertFileDoesNotExist($oldLogFile);
            $this->assertFileExists($freshLogFile);
        } finally {
            if (File::exists($oldLogFile)) {
                File::delete($oldLogFile);
            }
            if (File::exists($freshLogFile)) {
                File::delete($freshLogFile);
            }
        }
    }
}
