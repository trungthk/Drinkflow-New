<?php

declare(strict_types=1);

use App\Services\Code\CodeGeneratorService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            if (! Schema::hasColumn('campaigns', 'code')) {
                $table->string('code', 32)->nullable()->unique()->after('room_id');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'code')) {
                $table->string('code', 32)->nullable()->unique()->after('room_user_id');
            }
        });

        Schema::table('debts', function (Blueprint $table): void {
            if (! Schema::hasColumn('debts', 'code')) {
                $table->string('code', 32)->nullable()->unique()->after('room_user_id');
            }
        });

        // Backfill existing campaigns
        $campaigns = DB::table('campaigns')->whereNull('code')->get();
        foreach ($campaigns as $campaign) {
            DB::table('campaigns')->where('id', $campaign->id)->update([
                'code' => CodeGeneratorService::generateCampaignCode(),
            ]);
        }

        // Backfill existing orders
        $orders = DB::table('orders')->whereNull('code')->get();
        foreach ($orders as $order) {
            DB::table('orders')->where('id', $order->id)->update([
                'code' => CodeGeneratorService::generateOrderCode(),
            ]);
        }

        // Backfill existing debts
        $debts = DB::table('debts')->whereNull('code')->get();
        foreach ($debts as $debt) {
            DB::table('debts')->where('id', $debt->id)->update([
                'code' => CodeGeneratorService::generateDebtCode(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            if (Schema::hasColumn('debts', 'code')) {
                $table->dropColumn('code');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'code')) {
                $table->dropColumn('code');
            }
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('campaigns', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
