<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing enforcement and online payments.
     *
     * `admins.billing_suspended_at` marks a suspension made automatically for unpaid invoices, so it
     * is lifted automatically once they are settled (a manual suspension never is).
     * `admin_payments.gateway_transaction_id` makes payment-gateway notifications idempotent.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->timestamp('billing_suspended_at')->nullable()->after('status');
        });

        Schema::table('admin_payments', function (Blueprint $table): void {
            $table->string('gateway_transaction_id', 120)->nullable()->after('reference')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('admin_payments', function (Blueprint $table): void {
            $table->dropUnique(['gateway_transaction_id']);
            $table->dropColumn('gateway_transaction_id');
        });

        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn('billing_suspended_at');
        });
    }
};
