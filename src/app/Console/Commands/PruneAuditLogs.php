<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogs extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:prune-audit-logs';

    /** @var string */
    protected $description = 'Delete expired audit logs and clean up orphaned admin audit links.';

    /**
     * Remove audit logs that are past their configured retention period.
     *
     * @return int Process exit code.
     */
    public function handle(): int
    {
        $now = now();
        $auditDays = max(1, (int) config('retention.audit_logs_days', 30));

        // 1. Prune Audit Logs (Cascade deletes admin_audit_logs via PostgreSQL FK)
        $auditLogs = AuditLog::query()->where('created_at', '<', $now->copy()->subDays($auditDays))->delete();

        // 2. Clean up any orphaned admin_audit_logs records
        $orphanedAdminAudits = DB::table('admin_audit_logs')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('audit_logs')
                    ->whereColumn('audit_logs.id', 'admin_audit_logs.audit_log_id');
            })
            ->delete();

        $this->info("Pruned {$auditLogs} audit logs ({$orphanedAdminAudits} orphan admin audit links).");

        return self::SUCCESS;
    }
}
