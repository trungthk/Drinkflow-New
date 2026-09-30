<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agent subscriptions to a package, with the price and room limit frozen at activation.
     *
     * An Agent has at most one current (`active`) subscription: `current_admin_id` only holds the
     * admin ID on the active row and its unique index enforces that at the database level.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('admin_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('status', 20);
            $table->unsignedBigInteger('current_admin_id')->nullable()
                ->virtualAs("CASE WHEN status = 'active' THEN admin_id ELSE NULL END");
            $table->unsignedBigInteger('price_snapshot');
            $table->unsignedInteger('room_limit_snapshot');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('approved_by_superadmin_id')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->timestamps();

            $table->unique('current_admin_id');
            $table->index(['admin_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_subscriptions');
    }
};
