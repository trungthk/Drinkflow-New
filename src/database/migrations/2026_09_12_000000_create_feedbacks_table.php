<?php

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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_user_id')->nullable()->constrained('global_users')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('subsystem', 50)->default('all');
            $table->text('content');
            $table->string('user_display_name', 150)->nullable();
            $table->string('department_name', 150)->nullable();
            $table->timestamps();

            $table->index(['global_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
