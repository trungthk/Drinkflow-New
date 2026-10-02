<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguishes an Agent invited by a Superadmin from a self-service registration.
 *
 * An invited Agent starts `pending` with `invited_at` set, no verification email and an unusable
 * sign-in value; it becomes `active` only after the invited person opens the signed activation link
 * and chooses its sign-in value. `registered_at` keeps its own meaning (self-service sign-up), so the
 * review queue and the invitations never mix.
 *
 * `invited_at` is a system-managed timestamp: it is written with forceFill(), so it does not need to
 * be mass assignable.
 */
return new class extends Migration
{
    /**
     * Add the invitation timestamp to the admins table.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->timestamp('invited_at')->nullable()->after('registered_at');

            $table->index(['status', 'invited_at']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropIndex(['status', 'invited_at']);
            $table->dropColumn('invited_at');
        });
    }
};
