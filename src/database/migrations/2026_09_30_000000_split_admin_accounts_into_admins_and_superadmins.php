<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy admin statuses and the Admin lifecycle status that replaces each of them.
     *
     * @var array<string, string>
     */
    private const STATUS_MAP = [
        'inactive' => 'suspended',
        'blocked' => 'suspended',
        'disabled' => 'cancelled',
    ];

    /**
     * Separate Admin (Agent) accounts from platform Superadmins.
     *
     * `admin_accounts` is renamed to `admins` so every row keeps its ID and every foreign key that
     * points to it (rooms, campaigns, payments, audit…) stays valid. Legacy statuses are mapped to
     * the Admin lifecycle (pending/active/suspended/rejected/cancelled). The new `superadmins` table
     * starts empty: existing superadmin rows are moved there by the account data migration (T03).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::rename('admin_accounts', 'admins');

        foreach (self::STATUS_MAP as $legacy => $status) {
            DB::table('admins')->where('status', $legacy)->update(['status' => $status]);
        }

        Schema::create('superadmins', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->text('phone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('department')->nullable();
            $table->string('password');
            $table->string('status')->default('active')->index();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Restore `admin_accounts` with the closest legacy statuses.
     *
     * Pending and rejected registrations did not exist before; they become `inactive`.
     * Rows added to `superadmins` after this migration are dropped with the table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('superadmins');

        DB::table('admins')->where('status', 'suspended')->update(['status' => 'blocked']);
        DB::table('admins')->where('status', 'cancelled')->update(['status' => 'disabled']);
        DB::table('admins')->whereIn('status', ['pending', 'rejected'])->update(['status' => 'inactive']);

        Schema::rename('admins', 'admin_accounts');
    }
};
