<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create admin audit links and personal notifications. @return void */
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->foreignId('admin_id')->constrained('admin_accounts')->cascadeOnDelete();
            $table->foreignId('audit_log_id')->constrained('audit_logs')->cascadeOnDelete();
            $table->primary(['admin_id', 'audit_log_id']);
        });

        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admin_accounts')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audit_log_id')->nullable()->constrained('audit_logs')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['admin_id', 'read_at', 'created_at']);
        });
    }

    /** Remove admin activity links and notifications. @return void */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('admin_audit_logs');
    }
};
