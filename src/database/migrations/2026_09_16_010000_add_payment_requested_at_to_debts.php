<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the member payment-request timestamp to debts.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'payment_approved_by_admin_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('payment_approved_by_admin_id');
            });
        }

        $obsoleteOrderColumns = array_values(array_filter([
            Schema::hasColumn('orders', 'payment_confirmation_requested_at') ? 'payment_confirmation_requested_at' : null,
            Schema::hasColumn('orders', 'payment_confirmation_content') ? 'payment_confirmation_content' : null,
        ]));
        if ($obsoleteOrderColumns !== []) {
            Schema::table('orders', function (Blueprint $table) use ($obsoleteOrderColumns): void {
                $table->dropColumn($obsoleteOrderColumns);
            });
        }

        Schema::table('debts', function (Blueprint $table): void {
            $table->timestamp('payment_requested_at')->nullable()->after('status');
        });
    }

    /**
     * Remove the member payment-request timestamp from debts.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->dropColumn('payment_requested_at');
        });
    }
};
