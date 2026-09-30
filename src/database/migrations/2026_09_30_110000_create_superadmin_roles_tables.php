<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superadmin roles: named permission sets (each permission with its scope) that can be applied to
     * a Superadmin. A role is a template: applying it copies its grants to superadmin_permissions, which
     * stays the only source the Gates read.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('superadmin_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('superadmin_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('superadmin_role_id')->constrained('superadmin_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('scope', 20);
            $table->timestamps();

            // Explicit name: the generated one exceeds MySQL's 64-character identifier limit.
            $table->unique(['superadmin_role_id', 'permission_id'], 'superadmin_role_permission_unique');
        });

        Schema::table('superadmins', function (Blueprint $table): void {
            $table->foreignId('superadmin_role_id')->nullable()->after('status')->constrained('superadmin_roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('superadmins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('superadmin_role_id');
        });
        Schema::dropIfExists('superadmin_role_permissions');
        Schema::dropIfExists('superadmin_roles');
    }
};
