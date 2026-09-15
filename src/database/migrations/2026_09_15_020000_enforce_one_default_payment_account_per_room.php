<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Enforce one default payment account per room at database level. */
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('UPDATE payment_accounts SET is_default = false WHERE is_default = true AND id NOT IN (SELECT MAX(id) FROM payment_accounts WHERE is_default = true GROUP BY room_id)');
            DB::statement('CREATE UNIQUE INDEX payment_accounts_one_default_per_room ON payment_accounts (room_id) WHERE is_default = true');
        }
    }

    /** Remove the partial unique index. */
    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX payment_accounts_one_default_per_room');
        }
    }
};
