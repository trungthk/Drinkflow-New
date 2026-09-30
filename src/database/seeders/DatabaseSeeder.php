<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Room;
use App\Models\Superadmin;
use App\Services\Authorization\PermissionCatalogService;
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
        $admin = Admin::updateOrCreate(
            ['email' => 'admin@drinkflow.local'],
            ['name' => 'DrinkFlow Admin', 'password' => Hash::make('drinkflow2026'), 'status' => 'active'],
        );
        // Platform superadmin signs in on the separate superadmin guard (/superadmin/login).
        $superadmin = Superadmin::updateOrCreate(
            ['email' => 'superadmin@drinkflow.local'],
            ['name' => 'DrinkFlow Superadmin', 'password' => Hash::make('drinkflow2026'), 'status' => 'active'],
        );
        app(PermissionCatalogService::class)->grantAll($superadmin);

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

        // 3. Demo subscription packages (production packages are created by Superadmins).
        foreach ([
            ['starter', 'Starter', 0, 1, 1],
            ['business', 'Business', 100000, 10, 2],
            ['enterprise', 'Enterprise', 500000, 50, 3],
        ] as [$code, $name, $price, $roomLimit, $sortOrder]) {
            \App\Models\Package::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'monthly_price' => $price, 'room_limit' => $roomLimit, 'status' => 'active', 'sort_order' => $sortOrder],
            );
        }

        // 9. System Versions
        $this->call(VersionSeeder::class);
    }
}
