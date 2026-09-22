<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            // 'original': tính nợ trả riêng theo đúng đơn giá món, không cộng phí ship và không trừ giảm giá/chiết khấu chung.
            // 'campaign_prorated': tính nợ trả riêng có phân bổ theo tỷ lệ phí ship và giảm giá/chiết khấu chung của cả chiến dịch.
            $table->string('self_paid_price_basis')->default('original')->after('discount');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            // Món được đánh dấu "trả riêng": không được sponsor hỗ trợ, ghi nợ trực tiếp cho người đặt.
            $table->boolean('is_self_paid')->default(false)->after('note');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('is_self_paid');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn('self_paid_price_basis');
        });
    }
};
