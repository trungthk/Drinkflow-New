<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record Superadmin authorship on platform records written from the superadmin console.
     *
     * System settings and release versions are only edited by Superadmins. Their author used to be
     * stored in `*_admin_id` columns pointing to `admins`; with Superadmins in their own table an ID
     * written there would break the foreign key or point to an unrelated Admin. The legacy columns
     * are kept for existing rows.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->foreignId('updated_by_superadmin_id')->nullable()->after('updated_by_admin_id')
                ->constrained('superadmins')->nullOnDelete();
        });
        Schema::table('versions', function (Blueprint $table): void {
            $table->foreignId('created_by_superadmin_id')->nullable()->after('created_by_admin_id')
                ->constrained('superadmins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_superadmin_id');
        });
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by_superadmin_id');
        });
    }
};
