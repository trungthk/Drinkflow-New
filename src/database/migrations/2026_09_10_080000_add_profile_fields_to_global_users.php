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
        Schema::table('global_users', function (Blueprint $table) {
            if (!Schema::hasColumn('global_users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('global_users', 'desk_location')) {
                $table->string('desk_location')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('global_users', 'delivery_location')) {
                $table->string('delivery_location')->nullable()->after('desk_location');
            }
            if (!Schema::hasColumn('global_users', 'preferences')) {
                $table->json('preferences')->nullable()->after('delivery_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('global_users', 'preferences')) {
                $columns[] = 'preferences';
            }
            if (Schema::hasColumn('global_users', 'delivery_location')) {
                $columns[] = 'delivery_location';
            }
            if (Schema::hasColumn('global_users', 'desk_location')) {
                $columns[] = 'desk_location';
            }
            if (Schema::hasColumn('global_users', 'phone')) {
                $columns[] = 'phone';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
