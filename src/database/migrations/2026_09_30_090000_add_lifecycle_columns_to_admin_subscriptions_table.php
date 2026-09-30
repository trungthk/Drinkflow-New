<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscription lifecycle: downgrade scheduled for the period end, cancellation at period end,
     * and the credit of an upgraded subscription's unused period (deducted from the first invoice).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('admin_subscriptions', function (Blueprint $table): void {
            $table->foreignId('scheduled_package_id')->nullable()->after('package_id')->constrained('packages')->nullOnDelete();
            $table->boolean('cancel_at_period_end')->default(false)->after('expires_at');
            $table->unsignedBigInteger('proration_credit')->default(0)->after('price_snapshot');
            $table->foreignId('previous_subscription_id')->nullable()->after('admin_id')->constrained('admin_subscriptions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('admin_subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('scheduled_package_id');
            $table->dropConstrainedForeignId('previous_subscription_id');
            $table->dropColumn(['cancel_at_period_end', 'proration_credit']);
        });
    }
};
