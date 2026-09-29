<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow a `debts` row to act as a consolidated payment request (the "parent").
     *
     * Campaign debts stay the "children": `parent_id` points to the request they were bundled into.
     * The parent itself has no campaign (`campaign_id = NULL`), always has `parent_id = NULL`,
     * and stores the reviewer, review time and rejection reason.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->unsignedBigInteger('campaign_id')->nullable()->change();
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->timestamp('reviewed_at')->nullable()->after('payment_content');
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable()->after('reviewed_at');
            $table->text('review_reason')->nullable()->after('reviewed_by_admin_id');

            $table->index('parent_id');
            $table->index(['room_user_id', 'status']);
            $table->foreign('parent_id')->references('id')->on('debts')->restrictOnDelete();
            $table->foreign('reviewed_by_admin_id')->references('id')->on('admin_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * `campaign_id` stays nullable: payment-request rows may already exist and cannot get a campaign back.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['reviewed_by_admin_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['room_user_id', 'status']);
            $table->dropColumn(['parent_id', 'reviewed_at', 'reviewed_by_admin_id', 'review_reason']);
        });
    }
};
