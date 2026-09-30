<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Room ownership: the owning Agent carries the subscription, quota and platform billing of the room.
     *
     * `admin_rooms` keeps meaning operational access (the owner is always a member of it). Existing
     * rooms are mapped only when exactly one Admin is assigned; rooms with none or several Admins stay
     * without owner and are listed by `php artisan rooms:ownership` for a manual decision.
     *
     * @return void
     */
    public function up(): void
    {
        // Read the mapping first and alter without FK checks: SQLite rebuilds the table to add a
        // foreign key, which would otherwise cascade-delete the admin_rooms rows.
        $single = DB::table('admin_rooms')
            ->select('room_id', DB::raw('MIN(admin_id) as admin_id'))
            ->groupBy('room_id')
            ->havingRaw('COUNT(*) = 1')
            ->get();

        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->foreignId('owner_admin_id')->nullable()->after('id')->constrained('admins')->nullOnDelete();
                $table->index(['owner_admin_id', 'status']);
            });
        });

        foreach ($single as $row) {
            DB::table('rooms')->where('id', $row->room_id)->whereNull('owner_admin_id')->update(['owner_admin_id' => $row->admin_id]);
        }
    }

    /**
     * Reverse the migrations. Ownership data is dropped; `admin_rooms` is untouched.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->dropIndex(['owner_admin_id', 'status']);
                $table->dropConstrainedForeignId('owner_admin_id');
            });
        });
    }
};
