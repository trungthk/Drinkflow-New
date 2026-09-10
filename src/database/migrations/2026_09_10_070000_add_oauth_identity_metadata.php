<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_identities', function (Blueprint $table): void {
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('oauth_identities', function (Blueprint $table): void {
            $table->dropColumn(['linked_at', 'last_login_at']);
        });
    }
};
