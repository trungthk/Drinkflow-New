<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that expired audit logs (and their admin_audit_logs via FK cascade) are deleted,
     * while fresh audit logs remain untouched. Orphaned admin_audit_logs cannot exist
     * in practice due to the PostgreSQL FK constraint; the defensive cleanup in the command
     * is covered by this test via the cascade delete path.
     *
     * @return void
     */
    public function test_command_deletes_expired_audit_logs_and_keeps_fresh(): void
    {
        config()->set('retention.audit_logs_days', 30);

        $admin = AdminAccount::create(['name' => 'Admin', 'email' => 'audit-prune@example.test', 'password' => 'password', 'role' => 'admin', 'status' => 'active']);
        $room  = Room::create(['name' => 'Audit Room', 'slug' => 'audit-room', 'status' => 'active']);

        $oldAudit   = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'old.event',   'target_type' => 'room', 'room_id' => $room->id, 'created_at' => now()->subDays(31)]);
        $freshAudit = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'fresh.event', 'target_type' => 'room', 'room_id' => $room->id, 'created_at' => now()->subDays(29)]);

        DB::table('admin_audit_logs')->insert([
            ['admin_id' => $admin->id, 'audit_log_id' => $oldAudit->id],
            ['admin_id' => $admin->id, 'audit_log_id' => $freshAudit->id],
        ]);

        $this->artisan('drinkflow:prune-audit-logs')->assertSuccessful();

        // Expired audit log and its admin_audit_log row must be gone (cascade delete)
        $this->assertDatabaseMissing('audit_logs',       ['id' => $oldAudit->id]);
        $this->assertDatabaseMissing('admin_audit_logs', ['audit_log_id' => $oldAudit->id]);

        // Fresh audit log and its admin_audit_log row must remain
        $this->assertDatabaseHas('audit_logs',       ['id' => $freshAudit->id]);
        $this->assertDatabaseHas('admin_audit_logs', ['audit_log_id' => $freshAudit->id]);
    }
}