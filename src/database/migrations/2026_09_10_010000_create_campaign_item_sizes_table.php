<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('campaign_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price_delta')->default(0);
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['campaign_item_id', 'name']);
        });
    }
    public function down(): void { Schema::dropIfExists('campaign_item_sizes'); }
};
