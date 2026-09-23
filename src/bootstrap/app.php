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
    // Listeners are registered explicitly in AppServiceProvider. Auto-discovery would register every
    // App\Listeners\*::handle() a second time and fire each notification twice.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'global.user' => \App\Http\Middleware\ResolveGlobalUser::class,
            'room.user' => \App\Http\Middleware\ResolveRoomUser::class,
            'admin.room' => \App\Http\Middleware\EnsureAdminRoomAccess::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperadmin::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'user.has_rooms' => \App\Http\Middleware\EnsureUserHasRooms::class,
            'user.active_room' => \App\Http\Middleware\EnsureUserHasActiveRoom::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('admin', 'admin/*', 'superadmin/*')
                ? route('admin.login.page')
                : route('auth.google');
        });
        $middleware->validateCsrfTokens(except: ['logout']);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureActiveAdmin::class,
            \App\Http\Middleware\SecurityHeaders::class,
            // Needs the session, the admin guard and the matched route (superadmin bypass, login
            // routes), so it lives in the web group rather than the global stack.
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);
        // Apply the session locale right after the session starts and before route model binding,
        // so a 404 thrown by a missing {room}/{campaign} model is rendered in the user's language.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\SetLocale::class,
        );
        $middleware->append(\App\Http\Middleware\SanitizeInputStrings::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
