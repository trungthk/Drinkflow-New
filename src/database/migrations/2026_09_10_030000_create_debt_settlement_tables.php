<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('debt_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete();
            $t->string('type');
            $t->bigInteger('amount');
            $t->text('reason');
            $t->unsignedBigInteger('before_amount');
            $t->unsignedBigInteger('after_amount');
            $t->timestamps();
        });
        Schema::create('debt_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('amount');
            $t->string('payment_method');
            $t->string('reference')->nullable();
            $t->timestamp('paid_at')->useCurrent();
            $t->foreignId('created_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debt_adjustments');
    }
};
