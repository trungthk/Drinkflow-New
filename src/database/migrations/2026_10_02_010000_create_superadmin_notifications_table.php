<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal notifications addressed to platform Superadmins.
 *
 * Kept apart from `admin_notifications` on purpose: that table's `admin_id` is a foreign key to
 * `admins` (Agents), so a Superadmin inbox stored there would either break the constraint or mix
 * platform alerts with room-level Agent notifications. Platform alerts (a new Agent registration
 * waiting for review, and later billing or security events) live here, one row per recipient, so
 * every Superadmin reads and clears its own copy.
 */
return new class extends Migration
{
    /**
     * Create the superadmin notification table.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('superadmin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('superadmin_id')->constrained('superadmins')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Inbox queries always filter one account and order/filter by read state.
            $table->index(['superadmin_id', 'read_at', 'created_at']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('superadmin_notifications');
    }
};
