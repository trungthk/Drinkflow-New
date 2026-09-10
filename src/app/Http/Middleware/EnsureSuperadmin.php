<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class EnsureSuperadmin { public function handle(Request $request,Closure $next): Response { $admin=$request->user('admin'); abort_unless($admin?->isSuperadmin() && $admin->isActive(),403); return $next($request); } }
