<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
            'user.has_rooms' => \App\Http\Middleware\EnsureUserHasRooms::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('admin', 'admin/*', 'superadmin/*')
                ? route('admin.login.page')
                : route('auth.google');
        });
        $middleware->validateCsrfTokens(except: ['admin/*', 'superadmin/*', 'logout']);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureActiveAdmin::class,
        ]);
        $middleware->append(\App\Http\Middleware\SanitizeInputStrings::class);
        $middleware->append(\App\Http\Middleware\CheckMaintenanceMode::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
