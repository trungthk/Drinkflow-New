<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * T19 + T20: rooms.owner_admin_id and the mapping of existing rooms (never guessed).
 */
class RoomOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email): Admin
    {
        return Admin::create(['name' => $email, 'email' => $email, 'password' => 'password123', 'status' => 'active']);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_09_30_080000_add_owner_admin_id_to_rooms_table.php');
    }

    public function test_rooms_have_an_owner_column(): void
    {
        $this->assertTrue(Schema::hasColumn('rooms', 'owner_admin_id'));
    }

    public function test_migration_maps_only_rooms_with_exactly_one_admin(): void
    {
        $migration = $this->migration();
        $migration->down();
        $alice = $this->admin('alice@drinkflow.test');
        $bob = $this->admin('bob@drinkflow.test');
        $ids = [];
        foreach (['single', 'shared', 'orphan'] as $slug) {
            $ids[$slug] = DB::table('rooms')->insertGetId(['name' => $slug, 'slug' => $slug, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('admin_rooms')->insert([
            ['admin_id' => $alice->id, 'room_id' => $ids['single']],
            ['admin_id' => $alice->id, 'room_id' => $ids['shared']],
            ['admin_id' => $bob->id, 'room_id' => $ids['shared']],
        ]);

        $migration->up();

        $this->assertSame($alice->id, (int) DB::table('rooms')->where('id', $ids['single'])->value('owner_admin_id'));
        $this->assertNull(DB::table('rooms')->where('id', $ids['shared'])->value('owner_admin_id'));
        $this->assertNull(DB::table('rooms')->where('id', $ids['orphan'])->value('owner_admin_id'));
        // admin_rooms is not asserted here: inside the RefreshDatabase transaction SQLite cannot turn
        // foreign keys off, so rebuilding `rooms` cascades to it. MySQL alters in place (verified manually).
    }

    public function test_command_reports_unresolved_rooms_and_assigns_an_owner(): void
    {
        $alice = $this->admin('alice@drinkflow.test');
        $bob = $this->admin('bob@drinkflow.test');
        $room = Room::create(['name' => 'Shared', 'slug' => 'shared', 'status' => 'active']);
        $room->admins()->attach([$alice->id, $bob->id]);

        $this->artisan('rooms:ownership')->expectsOutputToContain('1 room(s) without owner')->assertSuccessful();
        $this->artisan('rooms:ownership', ['--room' => 'shared', '--admin' => 'bob@drinkflow.test'])->assertSuccessful();

        $this->assertSame($bob->id, $room->fresh()->owner_admin_id);
        $this->assertTrue($room->admins()->whereKey($bob->id)->exists());
        $this->assertDatabaseHas('audit_logs', ['event' => 'room.owner_changed', 'target_id' => $room->id]);
        $this->artisan('rooms:ownership')->expectsOutputToContain('Every room has an owner')->assertSuccessful();
    }

    public function test_command_rejects_unknown_room_or_admin(): void
    {
        $this->artisan('rooms:ownership', ['--room' => 'missing', '--admin' => '999'])->assertFailed();
    }

    public function test_deleting_the_owner_keeps_the_room(): void
    {
        $alice = $this->admin('alice@drinkflow.test');
        $room = Room::create(['name' => 'Team', 'slug' => 'team', 'status' => 'active', 'owner_admin_id' => $alice->id]);

        $alice->delete();

        $this->assertNull($room->fresh()->owner_admin_id);
    }
}
