<?php

declare(strict_types=1);

/*
 * Local seeding of the platform owner account (non-production only).
 *
 * No credential is a literal in the code: set PLATFORM_SEED_OWNER_VALUE in the local .env to choose
 * the sign-in value of the seeded owner, otherwise `php artisan db:seed` generates one and prints it
 * once on the console. In production nothing is read from here, because the seeder only creates this
 * account outside production.
 */
return [
    // Email of the seeded platform owner.
    'email' => env('PLATFORM_SEED_OWNER_EMAIL', 'superadmin@drinkflow.local'),
    // Display name of the seeded platform owner.
    'name' => env('PLATFORM_SEED_OWNER_NAME', 'DrinkFlow Superadmin'),
    // Sign-in value of the seeded owner; empty means "generate a random one".
    'value' => env('PLATFORM_SEED_OWNER_VALUE'),
];
