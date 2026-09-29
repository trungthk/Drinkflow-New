<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Room-domain columns that can reference a legacy superadmin row in `admins`.
     *
     * Superadmins no longer act inside rooms, and these foreign keys can only point to `admins`,
     * so the link is cleared; the original values are kept in the migration snapshot and restored
     * on rollback. The audit log still records who performed each action.
     *
     * @var array<string, string>
     */
    private const ROOM_REFERENCES = [
        'campaigns' => 'creator_admin_id',
        'orders' => 'placed_by_admin_id',
        'debt_payments' => 'created_by_admin_id',
        'debt_adjustments' => 'admin_id',
        'debts' => 'reviewed_by_admin_id',
        'crawler_previews' => 'admin_id',
    ];

    /**
     * Platform records authored by superadmins, as legacy column => superadmin column.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const AUTHOR_COLUMNS = [
        'versions' => ['created_by_admin_id', 'created_by_superadmin_id'],
        'system_settings' => ['updated_by_admin_id', 'updated_by_superadmin_id'],
    ];

    /**
     * Move every `admins` row with role = superadmin into `superadmins`, then drop `admins.role`.
     *
     * Each account is moved exactly once: an existing superadmin with the same email is reused
     * instead of duplicated. References are re-pointed (audit actor, versions, system settings) or
     * detached (rooms, notifications, admin audit pivot, room-domain authorship). The full original
     * row and every detached value are stored in `legacy_superadmin_accounts`, which is both the
     * trace of the migration and the source used by down() to restore the previous state.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('legacy_superadmin_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('legacy_admin_id')->unique();
            $table->foreignId('superadmin_id')->constrained('superadmins')->restrictOnDelete();
            $table->boolean('created_superadmin')->default(true);
            $table->json('snapshot');
            $table->timestamp('migrated_at');
        });

        DB::transaction(function (): void {
            $legacyRows = DB::table('admins')->where('role', 'superadmin')->orderBy('id')->get();
            foreach ($legacyRows as $legacy) {
                $this->moveAccount((array) $legacy);
            }
        });

        Schema::table('admins', function (Blueprint $table): void {
            $table->dropIndex('admin_accounts_role_index');
        });
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }

    /**
     * Restore `admins.role` and the legacy superadmin rows with every reference moved by up().
     *
     * Superadmin accounts created by up() are removed; accounts that already existed are kept.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('role')->default('admin')->after('password');
        });
        Schema::table('admins', function (Blueprint $table): void {
            $table->index('role', 'admin_accounts_role_index');
        });

        DB::transaction(function (): void {
            foreach (DB::table('legacy_superadmin_accounts')->orderBy('id')->get() as $record) {
                $this->restoreAccount($record);
            }
        });

        Schema::dropIfExists('legacy_superadmin_accounts');
    }

    /**
     * Move one legacy superadmin row and its references.
     *
     * @param array<string, mixed> $legacy Row from `admins`.
     * @return void
     */
    private function moveAccount(array $legacy): void
    {
        $oldId = (int) $legacy['id'];
        $email = mb_strtolower(trim((string) $legacy['email']));
        $existingId = DB::table('superadmins')->whereRaw('LOWER(email) = ?', [$email])->value('id');
        $superadminId = $existingId !== null ? (int) $existingId : (int) DB::table('superadmins')->insertGetId([
            'name' => $legacy['name'],
            'email' => $legacy['email'],
            'phone' => $legacy['phone'],
            'avatar_url' => $legacy['avatar_url'],
            'department' => $legacy['department'],
            'password' => $legacy['password'],
            'status' => $legacy['status'] === 'active' ? 'active' : 'suspended',
            'two_factor_enabled' => (bool) $legacy['two_factor_enabled'],
            'last_login_at' => $legacy['last_login_at'],
            'remember_token' => null,
            'created_at' => $legacy['created_at'],
            'updated_at' => $legacy['updated_at'],
        ]);

        // Audit entries written by this account are the ones linked through the admin pivot.
        $auditIds = DB::table('admin_audit_logs')->where('admin_id', $oldId)->pluck('audit_log_id')->map(static fn ($id): int => (int) $id)->all();
        $actorAuditIds = DB::table('audit_logs')->whereIn('id', $auditIds)->where('actor_type', 'superadmin')->where('actor_id', $oldId)->pluck('id')->all();
        DB::table('audit_logs')->whereIn('id', $actorAuditIds)->update(['actor_id' => $superadminId]);

        $authored = [];
        foreach (self::AUTHOR_COLUMNS as $table => [$legacyColumn, $superadminColumn]) {
            $authored[$table] = DB::table($table)->where($legacyColumn, $oldId)->pluck('id')->all();
            DB::table($table)->whereIn('id', $authored[$table])->update([$legacyColumn => null, $superadminColumn => $superadminId]);
        }

        $roomReferences = [];
        foreach (self::ROOM_REFERENCES as $table => $column) {
            $roomReferences[$table] = DB::table($table)->where($column, $oldId)->pluck('id')->all();
            DB::table($table)->whereIn('id', $roomReferences[$table])->update([$column => null]);
        }

        $snapshot = [
            'admin' => $legacy,
            'audit_actor_ids' => $actorAuditIds,
            'admin_audit_log_ids' => $auditIds,
            'authored' => $authored,
            'room_references' => $roomReferences,
            'admin_rooms' => DB::table('admin_rooms')->where('admin_id', $oldId)->get()->map(static fn ($row): array => (array) $row)->all(),
            'admin_notifications' => DB::table('admin_notifications')->where('admin_id', $oldId)->get()->map(static fn ($row): array => (array) $row)->all(),
        ];
        DB::table('admin_audit_logs')->where('admin_id', $oldId)->delete();
        DB::table('admin_rooms')->where('admin_id', $oldId)->delete();
        DB::table('admin_notifications')->where('admin_id', $oldId)->delete();

        DB::table('legacy_superadmin_accounts')->insert([
            'legacy_admin_id' => $oldId,
            'superadmin_id' => $superadminId,
            'created_superadmin' => $existingId === null,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'migrated_at' => now(),
        ]);
        DB::table('admins')->where('id', $oldId)->delete();
    }

    /**
     * Put one legacy superadmin back into `admins` with its references.
     *
     * @param object $record Row from `legacy_superadmin_accounts`.
     * @return void
     */
    private function restoreAccount(object $record): void
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = json_decode((string) $record->snapshot, true, 512, JSON_THROW_ON_ERROR);
        $oldId = (int) $record->legacy_admin_id;
        $superadminId = (int) $record->superadmin_id;

        DB::table('admins')->insert($snapshot['admin']);

        foreach ($snapshot['admin_rooms'] as $row) {
            DB::table('admin_rooms')->insert($row);
        }
        foreach ($snapshot['admin_notifications'] as $row) {
            DB::table('admin_notifications')->insert($row);
        }
        foreach ($snapshot['admin_audit_log_ids'] as $auditId) {
            if (DB::table('audit_logs')->where('id', $auditId)->exists()) {
                DB::table('admin_audit_logs')->insert(['admin_id' => $oldId, 'audit_log_id' => $auditId]);
            }
        }
        DB::table('audit_logs')->whereIn('id', $snapshot['audit_actor_ids'])->update(['actor_id' => $oldId]);

        foreach (self::AUTHOR_COLUMNS as $table => [$legacyColumn, $superadminColumn]) {
            DB::table($table)->whereIn('id', $snapshot['authored'][$table] ?? [])->update([$legacyColumn => $oldId, $superadminColumn => null]);
        }
        foreach (self::ROOM_REFERENCES as $table => $column) {
            DB::table($table)->whereIn('id', $snapshot['room_references'][$table] ?? [])->update([$column => $oldId]);
        }

        if ((bool) $record->created_superadmin) {
            DB::table('legacy_superadmin_accounts')->where('id', $record->id)->delete();
            foreach (self::AUTHOR_COLUMNS as $table => [, $superadminColumn]) {
                DB::table($table)->where($superadminColumn, $superadminId)->update([$superadminColumn => null]);
            }
            DB::table('superadmins')->where('id', $superadminId)->delete();
        }
    }
};
