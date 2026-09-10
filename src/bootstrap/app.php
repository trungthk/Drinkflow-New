<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'global.user' => \App\Http\Middleware\ResolveGlobalUser::class,
            'room.user' => \App\Http\Middleware\ResolveRoomUser::class,
            'admin.room' => \App\Http\Middleware\EnsureAdminRoomAccess::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperadmin::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
