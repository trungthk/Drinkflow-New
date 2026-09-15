<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Create campaign participation records with one response per room user. */
    public function up(): void
    {
        Schema::create('campaign_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'room_user_id']);
        });
    }

    /** Remove campaign participation records. */
    public function down(): void
    {
        Schema::dropIfExists('campaign_participants');
    }
};
