<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_user_id')->constrained('global_users')->cascadeOnDelete();
            $table->foreignId('room_user_id')->nullable()->constrained('room_users')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['global_user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('user_notifications'); }
};
