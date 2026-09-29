<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assignment of Agents (admins) to the Superadmins that manage them (`managed` scope).
     *
     * An Agent can be assigned to several Superadmins but has at most one primary Superadmin:
     * `primary_admin_id` only holds the admin ID on the primary row, and its unique index
     * enforces that at the database level (NULLs never collide).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('superadmin_admins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('superadmin_id')->constrained('superadmins')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('primary_admin_id')->nullable()
                ->virtualAs('CASE WHEN is_primary THEN admin_id ELSE NULL END');
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by_superadmin_id')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['superadmin_id', 'admin_id']);
            $table->unique('primary_admin_id');
            $table->index(['admin_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('superadmin_admins');
    }
};
