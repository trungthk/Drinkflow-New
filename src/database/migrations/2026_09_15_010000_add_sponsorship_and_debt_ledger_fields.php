<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Apply sponsorship policy and immutable debt-ledger fields. */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->string('sponsor_type')->default('none')->after('sponsor_name');
            $table->text('sponsor_description')->nullable()->after('sponsor_type');
        });
        Schema::table('campaign_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('sponsor_amount')->default(0)->after('base_price');
        });
        Schema::table('debts', function (Blueprint $table): void {
            $table->string('sponsor_type')->default('none')->after('sponsor_amount');
            $table->text('sponsor_description')->nullable()->after('sponsor_type');
            $table->index(['room_user_id', 'created_at']);
        });
    }

    /** Revert sponsorship policy and immutable debt-ledger fields. */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->dropIndex(['room_user_id', 'created_at']);
            $table->dropColumn(['sponsor_type', 'sponsor_description']);
        });
        Schema::table('campaign_items', function (Blueprint $table): void {
            $table->dropColumn('sponsor_amount');
        });
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['sponsor_type', 'sponsor_description']);
        });
    }
};
