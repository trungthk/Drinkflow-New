<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize legacy disabled payment accounts to the enum-backed inactive value.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('payment_accounts')->where('status', 'disabled')->update(['status' => 'inactive']);
    }

    /**
     * Keep normalized statuses when rolling back because disabled is not an enum value.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
