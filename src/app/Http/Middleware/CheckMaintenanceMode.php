<?php

namespace App\Http\Middleware;

use App\Services\System\SystemSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(SystemSettingsService::class);
        $active = (bool)$settings->get('maintenance.enabled', false);
        $starts = $settings->get('maintenance.starts_at');
        $ends = $settings->get('maintenance.ends_at');
        if ($starts && now()->lt($starts)) $active = false;
        if ($ends && now()->gt($ends)) $active = false;
        $loginRoute = in_array($request->route()?->getName(), ['admin.login', 'admin.login.page'], true);
        if ($active && !$loginRoute && !($request->user('admin')?->isSuperadmin())) return response()->json(['message' => 'Hệ thống đang bảo trì.'], 503);
        return $next($request);
    }
}
