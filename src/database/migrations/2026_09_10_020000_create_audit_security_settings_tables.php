<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->text('config_encrypted');
            $table->string('status')->default('disabled');
            $table->timestamps();
            $table->index(['room_id', 'status']);
        });
        Schema::create('system_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->text('config_encrypted');
            $table->string('status')->default('disabled');
            $table->timestamps();
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_secret')->default(false);
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('room_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
            $table->unique(['room_id', 'key']);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_uuid')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_type', 'actor_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['room_id', 'created_at']);
            $table->index('event');
        });
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('severity')->default('medium');
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->string('device_uuid')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['type', 'created_at']);
            $table->index(['actor_type', 'actor_id']);
        });
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('title');
            $table->text('changelog')->nullable();
            $table->date('release_date')->nullable();
            $table->boolean('force_refresh')->default(false);
            $table->boolean('important')->default(false);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['versions', 'security_events', 'audit_logs', 'room_settings', 'system_settings', 'system_notification_channels', 'notification_channels'] as $table)
            Schema::dropIfExists($table);
    }
};
