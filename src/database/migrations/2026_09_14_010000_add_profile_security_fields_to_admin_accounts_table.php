<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add editable profile and security preference fields for admin accounts. @return void */
    public function up(): void
    {
        Schema::table('admin_accounts', function (Blueprint $table): void {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('phone', 30)->nullable()->after('avatar_url');
            $table->string('department')->nullable()->after('phone');
            $table->boolean('two_factor_enabled')->default(false)->after('status');
        });
    }

    /** Remove editable admin profile and security preference fields. @return void */
    public function down(): void
    {
        Schema::table('admin_accounts', function (Blueprint $table): void {
            $table->dropColumn(['avatar_url', 'phone', 'department', 'two_factor_enabled']);
        });
    }
};
