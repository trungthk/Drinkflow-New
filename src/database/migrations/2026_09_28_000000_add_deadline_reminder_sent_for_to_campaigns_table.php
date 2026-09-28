<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The ordering deadline the "deadline is near" reminder was last sent for. Storing the deadline itself
     * (not the send time) means extending the deadline makes the campaign due for a new reminder automatically.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('deadline_reminder_sent_for')->nullable()->after('ordering_locked_at');
            $table->index(['status', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['status', 'deadline']);
            $table->dropColumn('deadline_reminder_sent_for');
        });
    }
};
