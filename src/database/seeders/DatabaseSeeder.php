<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed a rich, repeatable workspace for local testing, demos, and join-room flows. */
    public function run(): void
    {
        // Demo accounts use a well-known password, so production only receives reference data.
        if (app()->isProduction()) {
            $this->call(VersionSeeder::class);

            return;
        }

        // 1. Core Admins
        $admin = AdminAccount::updateOrCreate(
            ['email' => 'admin@drinkflow.local'],
            ['name' => 'DrinkFlow Admin', 'password' => Hash::make('drinkflow2026'), 'role' => 'admin', 'status' => 'active'],
        );
        AdminAccount::updateOrCreate(
            ['email' => 'superadmin@drinkflow.local'],
            ['name' => 'DrinkFlow Superadmin', 'password' => Hash::make('drinkflow2026'), 'role' => 'superadmin', 'status' => 'active'],
        );

        // 2. Demo Rooms
        $techRoom = Room::updateOrCreate(
            ['slug' => 'mens-est'],
            [
                'name' => 'Team Mens-Est',
                'description' => 'Không gian đặt đồ uống và trà chiều cho team Mens-Est.',
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'language' => 'vi',
            ],
        );

        $admin->rooms()->syncWithoutDetaching([$techRoom->id]);

        // 9. System Versions
        $this->call(VersionSeeder::class);
    }
}
