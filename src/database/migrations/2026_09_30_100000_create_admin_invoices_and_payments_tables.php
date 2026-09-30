<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform billing: invoices of Agent subscriptions and the payments recorded against them.
     *
     * This is platform finance (Superadmin ↔ Agent) and never shares tables with room finance
     * (debts/debt_payments between a room and its users). One invoice exists per subscription period:
     * the unique (admin_subscription_id, period_start) index makes invoice generation idempotent.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('admin_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->nullable()->unique();
            $table->foreignId('admin_id')->constrained('admins')->restrictOnDelete();
            $table->foreignId('admin_subscription_id')->constrained('admin_subscriptions')->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('credit')->default(0);
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('status', 20);
            $table->timestamp('issued_at');
            $table->timestamp('due_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['admin_subscription_id', 'period_start']);
            $table->index(['status', 'due_at']);
            $table->index(['admin_id', 'status']);
            $table->index('issued_at');
        });

        Schema::create('admin_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_invoice_id')->constrained('admin_invoices')->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 30);
            $table->string('reference', 120)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by_superadmin_id')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'paid_at']);
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_payments');
        Schema::dropIfExists('admin_invoices');
    }
};
