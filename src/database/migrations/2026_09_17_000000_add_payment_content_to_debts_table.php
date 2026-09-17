<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            if (!Schema::hasColumn('debts', 'payment_content')) {
                $table->string('payment_content')->nullable()->after('payment_requested_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            if (Schema::hasColumn('debts', 'payment_content')) {
                $table->dropColumn('payment_content');
            }
        });
    }
};
