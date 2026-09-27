<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Record which room admin placed an order on behalf of a member (null = placed by the member).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('placed_by_admin_id')->nullable()->after('parent_id')->constrained('admin_accounts')->nullOnDelete();
        });

        $this->restorePartialActiveOrderIndex();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('placed_by_admin_id');
        });

        $this->restorePartialActiveOrderIndex();
    }

    /**
     * SQLite rebuilds the table to alter foreign keys and drops the WHERE clause of partial indexes,
     * turning "one active order per member per campaign" into "one order ever". Recreate it as it was.
     */
    private function restorePartialActiveOrderIndex(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS orders_one_active_per_user_campaign');
        DB::statement("CREATE UNIQUE INDEX orders_one_active_per_user_campaign ON orders (campaign_id, room_user_id) WHERE status IN ('submitted','confirmed','ordering','ordered','delivering')");
    }
};
