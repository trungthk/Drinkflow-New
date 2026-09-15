<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add per-sponsor allocation details to campaigns. */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->json('sponsor_allocations')->nullable()->after('sponsor_description');
        });
    }

    /** Remove per-sponsor allocation details from campaigns. */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn('sponsor_allocations');
        });
    }
};
