<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * When set, a running campaign stops accepting orders from members (admin "khóa chiến dịch").
     * Who locked/unlocked it is kept in the audit log.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('ordering_locked_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('ordering_locked_at');
        });
    }
};
