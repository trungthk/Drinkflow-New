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
        $locale = session('locale', \App\Constants\AppLocale::DEFAULT);

        if (!\App\Constants\AppLocale::isValid($locale)) {
            $locale = \App\Constants\AppLocale::DEFAULT;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
