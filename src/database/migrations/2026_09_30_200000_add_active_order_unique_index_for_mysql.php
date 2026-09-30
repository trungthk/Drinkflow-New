<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Order statuses that count as "active" (App\Enums\OrderStatus::activeValues() when this ran). */
    private const ACTIVE = ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering'];

    /**
     * One active order per member per campaign on MySQL/MariaDB (UPG-01.1).
     *
     * MySQL has no partial indexes, so a generated column holds room_user_id only while the order is
     * active (NULL otherwise, and NULLs never collide) and a unique index covers (campaign_id, key).
     * SQLite/PostgreSQL keep the partial index created by the core migration.
     *
     * @return void
     * @throws RuntimeException When existing data already has duplicate active orders.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $duplicates = DB::table('orders')
            ->select('campaign_id', 'room_user_id', DB::raw('COUNT(*) AS total'))
            ->whereIn('status', self::ACTIVE)
            ->groupBy('campaign_id', 'room_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Duplicate active orders must be merged or cancelled before this migration (campaign_id/room_user_id): '
                .$duplicates->map(static fn ($row): string => "{$row->campaign_id}/{$row->room_user_id} ({$row->total})")->implode(', '));
        }

        $statuses = implode(',', array_map(static fn (string $status): string => "'{$status}'", self::ACTIVE));
        Schema::table('orders', function (Blueprint $table) use ($statuses): void {
            $table->unsignedBigInteger('active_order_key')->nullable()
                ->virtualAs("CASE WHEN status IN ({$statuses}) THEN room_user_id ELSE NULL END");
            $table->unique(['campaign_id', 'active_order_key'], 'orders_one_active_per_campaign_member');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_one_active_per_campaign_member');
            $table->dropColumn('active_order_key');
        });
    }
};
