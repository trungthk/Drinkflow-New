<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['vi', 'en', 'ja'];
        $locale = session('locale', 'vi');

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = 'vi';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
