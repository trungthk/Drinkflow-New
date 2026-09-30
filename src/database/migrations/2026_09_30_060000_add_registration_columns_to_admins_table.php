<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public Agent registration: company, requested package, email verification and review data.
     *
     * Existing Admins were created by a Superadmin, so their email is treated as verified.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('company')->nullable()->after('name');
            $table->foreignId('requested_package_id')->nullable()->after('status')->constrained('packages')->nullOnDelete();
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_superadmin_id')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            $table->index(['status', 'registered_at']);
        });

        DB::table('admins')->whereNull('email_verified_at')->update(['email_verified_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropIndex(['status', 'registered_at']);
            $table->dropConstrainedForeignId('requested_package_id');
            $table->dropConstrainedForeignId('reviewed_by_superadmin_id');
            $table->dropColumn(['company', 'email_verified_at', 'registered_at', 'reviewed_at', 'rejection_reason']);
        });
    }
};
