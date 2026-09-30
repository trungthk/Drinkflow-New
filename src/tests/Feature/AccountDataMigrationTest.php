<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * T01 + T03: legacy `admin_accounts` data reaches the new admins/superadmins tables without
 * duplicates or loss, and every step can be rolled back to the original state.
 */
class AccountDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** Account-split migrations, oldest first. */
    private const MIGRATIONS = [
        'migrations/2026_09_30_000000_split_admin_accounts_into_admins_and_superadmins.php',
        'migrations/2026_09_30_010000_add_superadmin_author_columns.php',
        'migrations/2026_09_30_020000_move_legacy_superadmins_out_of_admins.php',
    ];

    /** Later migrations that change `admins`; rolled back first (newest first) so the split can be undone. */
    private const LATER_ADMIN_MIGRATIONS = [
        'migrations/2026_09_30_070000_create_admin_subscriptions_table.php',
        'migrations/2026_09_30_060000_add_registration_columns_to_admins_table.php',
    ];

    /** @var array<int, Migration> */
    private array $migrations = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::LATER_ADMIN_MIGRATIONS as $path) {
            (require database_path($path))->down();
        }
        foreach (self::MIGRATIONS as $path) {
            $this->migrations[] = require database_path($path);
        }
        $this->rollBack();
    }

    private function rollBack(): void
    {
        foreach (array_reverse($this->migrations) as $migration) {
            $migration->down();
        }
    }

    private function migrate(): void
    {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    /**
     * Insert a legacy account row.
     *
     * @param int $id Account ID.
     * @param string $role admin or superadmin.
     * @param string $status Legacy status.
     * @param array<string, mixed> $extra Extra columns.
     * @return void
     */
    private function legacyAccount(int $id, string $role, string $status = 'active', array $extra = []): void
    {
        DB::table('admin_accounts')->insert($extra + [
            'id' => $id,
            'name' => 'Legacy '.$id,
            'email' => "legacy{$id}@drinkflow.test",
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function room(): int
    {
        return (int) DB::table('rooms')->insertGetId(['name' => 'Legacy Room', 'slug' => 'legacy-room', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_admin_accounts_keep_their_ids_and_get_lifecycle_statuses(): void
    {
        $this->legacyAccount(11, 'admin');
        $this->legacyAccount(12, 'admin', 'inactive');
        $this->legacyAccount(13, 'admin', 'blocked');
        $this->legacyAccount(14, 'admin', 'disabled');

        $this->migrate();

        $this->assertFalse(Schema::hasTable('admin_accounts'));
        $this->assertFalse(Schema::hasColumn('admins', 'role'));
        $this->assertSame(
            [11 => 'active', 12 => 'suspended', 13 => 'suspended', 14 => 'cancelled'],
            DB::table('admins')->orderBy('id')->pluck('status', 'id')->all(),
        );
        $this->assertSame(0, DB::table('superadmins')->count());
    }

    public function test_legacy_superadmins_move_once_with_their_references(): void
    {
        $roomId = $this->room();
        $phone = Crypt::encryptString('0900000009');
        $this->legacyAccount(21, 'admin');
        $this->legacyAccount(22, 'superadmin', 'active', ['phone' => $phone, 'remember_token' => 'old-token', 'two_factor_enabled' => false]);
        $this->legacyAccount(23, 'superadmin', 'blocked');

        // Activity of superadmin 22 written before the split.
        $auditId = (int) DB::table('audit_logs')->insertGetId(['actor_type' => 'superadmin', 'actor_id' => 22, 'event' => 'room.created', 'target_type' => 'room', 'target_id' => $roomId, 'created_at' => now()]);
        DB::table('admin_audit_logs')->insert(['admin_id' => 22, 'audit_log_id' => $auditId]);
        // An admin's own entry with the same actor_id must not be touched.
        $adminAuditId = (int) DB::table('audit_logs')->insertGetId(['actor_type' => 'admin', 'actor_id' => 22, 'event' => 'x', 'target_type' => 'room', 'target_id' => 1, 'created_at' => now()]);
        $versionId = (int) DB::table('versions')->insertGetId(['version' => 'v1.0.0', 'title' => 'First', 'created_by_admin_id' => 22, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('admin_rooms')->insert(['admin_id' => 22, 'room_id' => $roomId]);
        $campaignId = (int) DB::table('campaigns')->insertGetId(['room_id' => $roomId, 'name' => 'C', 'restaurant' => 'R', 'status' => 'closed', 'creator_admin_id' => 22, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('admin_notifications')->insert(['admin_id' => 22, 'room_id' => $roomId, 'type' => 't', 'title' => 'Note', 'created_at' => now(), 'updated_at' => now()]);

        $this->migrate();

        $this->assertSame([21], DB::table('admins')->pluck('id')->all());
        $this->assertSame(2, DB::table('superadmins')->count());
        $moved = DB::table('superadmins')->where('email', 'legacy22@drinkflow.test')->first();
        $this->assertSame('active', $moved->status);
        $this->assertNull($moved->remember_token);
        $this->assertSame('0900000009', Crypt::decryptString((string) $moved->phone));
        $this->assertTrue(Hash::check('password123', (string) $moved->password));
        $this->assertSame('suspended', DB::table('superadmins')->where('email', 'legacy23@drinkflow.test')->value('status'));

        $this->assertSame((int) $moved->id, (int) DB::table('audit_logs')->where('id', $auditId)->value('actor_id'));
        $this->assertSame(22, (int) DB::table('audit_logs')->where('id', $adminAuditId)->value('actor_id'));
        $this->assertSame(0, DB::table('admin_audit_logs')->count());
        $this->assertDatabaseHas('versions', ['id' => $versionId, 'created_by_admin_id' => null, 'created_by_superadmin_id' => $moved->id]);
        $this->assertSame(0, DB::table('admin_rooms')->count());
        $this->assertSame(0, DB::table('admin_notifications')->count());
        $this->assertNull(DB::table('campaigns')->where('id', $campaignId)->value('creator_admin_id'));
        $this->assertSame(2, DB::table('legacy_superadmin_accounts')->count());

        // Rolling back restores the original rows and references exactly.
        $this->rollBack();

        $this->assertSame(['admin', 'superadmin', 'superadmin'], DB::table('admin_accounts')->orderBy('id')->pluck('role')->all());
        $this->assertSame($phone, DB::table('admin_accounts')->where('id', 22)->value('phone'));
        $this->assertSame(22, (int) DB::table('audit_logs')->where('id', $auditId)->value('actor_id'));
        $this->assertTrue(DB::table('admin_audit_logs')->where(['admin_id' => 22, 'audit_log_id' => $auditId])->exists());
        $this->assertSame(22, (int) DB::table('versions')->where('id', $versionId)->value('created_by_admin_id'));
        $this->assertTrue(DB::table('admin_rooms')->where(['admin_id' => 22, 'room_id' => $roomId])->exists());
        $this->assertSame(22, (int) DB::table('campaigns')->where('id', $campaignId)->value('creator_admin_id'));
        $this->assertSame(1, DB::table('admin_notifications')->where('admin_id', 22)->count());
        $this->assertSame('blocked', DB::table('admin_accounts')->where('id', 23)->value('status'));

        // Running forward again gives the same result (no duplicates).
        $this->migrate();
        $this->assertSame(2, DB::table('superadmins')->count());
        $this->assertSame([21], DB::table('admins')->pluck('id')->all());
    }

    public function test_existing_superadmin_with_the_same_email_is_reused_not_duplicated(): void
    {
        $this->legacyAccount(31, 'superadmin');
        // Up to the author-columns step, then a superadmin with the same email already exists.
        $this->migrations[0]->up();
        $this->migrations[1]->up();
        $existingId = (int) DB::table('superadmins')->insertGetId(['name' => 'Seeded', 'email' => 'LEGACY31@drinkflow.test', 'password' => 'x', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->migrations[2]->up();

        $this->assertSame(1, DB::table('superadmins')->count());
        $this->assertSame('Seeded', DB::table('superadmins')->where('id', $existingId)->value('name'));
        $this->assertSame(0, DB::table('admins')->count());

        // Rollback keeps the pre-existing superadmin and restores the legacy row.
        $this->migrations[2]->down();
        $this->assertTrue(DB::table('superadmins')->where('id', $existingId)->exists());
        $this->assertSame('superadmin', DB::table('admins')->where('id', 31)->value('role'));
        $this->migrations[2]->up();
    }
}
