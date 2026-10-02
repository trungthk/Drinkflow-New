<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rooms from before the SaaS model have no owner, so they never appear under the Agent's own rooms
     * nor count toward its package quota. Each one is given to the Agent linked to it first in
     * `admin_rooms` (lowest pivot ID); any other linked Agents stay collaborators. Rooms nobody is
     * linked to are left unowned (a Superadmin can assign them from the room page).
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('rooms')
            ->whereNull('owner_admin_id')
            ->orderBy('id')
            ->pluck('id')
            ->each(static function (int|string $roomId): void {
                $ownerId = DB::table('admin_rooms')
                    ->join('admins', 'admins.id', '=', 'admin_rooms.admin_id')
                    ->where('admin_rooms.room_id', $roomId)
                    ->orderBy('admin_rooms.id')
                    ->value('admin_rooms.admin_id');
                if ($ownerId !== null) {
                    DB::table('rooms')->where('id', $roomId)->whereNull('owner_admin_id')->update(['owner_admin_id' => $ownerId]);
                }
            });
    }

    /**
     * Data migration: owners are kept on rollback (they may have changed rooms since).
     *
     * @return void
     */
    public function down(): void
    {
    }
};
