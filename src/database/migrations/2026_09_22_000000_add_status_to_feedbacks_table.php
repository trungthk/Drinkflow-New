<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the moderation status. New feedback is inactive until a superadmin approves it.
     */
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table): void {
            $table->string('status', 20)->default('inactive')->after('content');
            $table->index(['status', 'rating', 'created_at']);
        });

        // Feedback that already existed was publicly visible before moderation was introduced; keep it visible.
        DB::table('feedbacks')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table): void {
            $table->dropIndex(['status', 'rating', 'created_at']);
            $table->dropColumn('status');
        });
    }
};
