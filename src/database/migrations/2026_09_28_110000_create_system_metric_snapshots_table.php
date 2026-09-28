<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periodic infrastructure snapshots (drinkflow:capture-system-metrics) so the superadmin dashboard can show
 * queue, storage and realtime history instead of only the live health check.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('captured_at')->index();
            $table->boolean('database_ok')->default(true);
            $table->unsignedInteger('pending_jobs')->nullable();
            $table->unsignedInteger('failed_jobs')->default(0);
            $table->unsignedBigInteger('storage_used_bytes')->nullable();
            $table->unsignedBigInteger('storage_total_bytes')->nullable();
            $table->boolean('socket_ok')->nullable();
            $table->unsignedInteger('socket_connections')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_metric_snapshots');
    }
};
