<?php
namespace App\Http\Middleware;
use App\Services\System\SystemSettingsService; use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class CheckMaintenanceMode { public function handle(Request $request,Closure $next): Response { if(app(SystemSettingsService::class)->get('maintenance.enabled',false) && !($request->user('admin')?->isSuperadmin())) return response()->json(['message'=>'Hệ thống đang bảo trì.'],503); return $next($request); } }
