<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permission catalog at the time of this migration (App\Enums\Permission).
     *
     * Kept as a literal snapshot so the migration gives the same result even after the enum grows;
     * later permissions are added by `php artisan permissions:sync`.
     *
     * @var array<int, string>
     */
    private const INITIAL_PERMISSIONS = [
        'agent.view', 'agent.manage', 'agent.approve',
        'package.view', 'package.manage',
        'subscription.view', 'subscription.manage',
        'revenue.view',
        'debt.view', 'debt.manage',
        'room.view', 'room.manage',
        'global_user.view', 'global_user.manage',
        'feedback.view', 'feedback.manage',
        'version.view', 'version.manage',
        'settings.view', 'settings.manage',
        'security.view',
        'audit.view',
        'queue.view', 'queue.manage',
        'superadmin.view', 'superadmin.manage',
    ];

    /**
     * Create the Superadmin permission catalog and assignments.
     *
     * Superadmins that already exist receive every permission with the `all` scope, so they keep
     * the full access they had before permissions are enforced (T05).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('group', 50)->index();
            $table->timestamps();
        });

        Schema::create('superadmin_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('superadmin_id')->constrained('superadmins')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('scope', 20)->default('all');
            $table->foreignId('granted_by_superadmin_id')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['superadmin_id', 'permission_id']);
            $table->index(['permission_id', 'scope']);
        });

        $now = now();
        DB::table('permissions')->insert(array_map(static fn (string $key): array => [
            'key' => $key,
            'group' => strstr($key, '.', true),
            'created_at' => $now,
            'updated_at' => $now,
        ], self::INITIAL_PERMISSIONS));

        $permissionIds = DB::table('permissions')->pluck('id');
        foreach (DB::table('superadmins')->pluck('id') as $superadminId) {
            DB::table('superadmin_permissions')->insert($permissionIds->map(static fn ($permissionId): array => [
                'superadmin_id' => $superadminId,
                'permission_id' => $permissionId,
                'scope' => 'all',
                'granted_by_superadmin_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('superadmin_permissions');
        Schema::dropIfExists('permissions');
    }
};
