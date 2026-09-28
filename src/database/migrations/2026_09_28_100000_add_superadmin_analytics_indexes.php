<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes backing the system-wide superadmin dashboard analytics (period filters on created_at
 * combined with status/severity, per-room campaign activity).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['room_id', 'created_at']);
        });
        Schema::table('security_events', function (Blueprint $table) {
            $table->index(['severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'created_at']);
        });
        Schema::table('security_events', function (Blueprint $table) {
            $table->dropIndex(['severity', 'created_at']);
        });
    }
};
