<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class TermsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $version = 'v1.0';
        $appVersion = config('app.version', 'v2.3.0');
        $effectiveDate = '10/09/2026';
        $googleAuthUrl = route('auth.google');
        $termsUrl = url('/terms');
        $versionsUrl = url('/versions');
        $landingUrl = route('landing');

        return view('public.terms', [
            'version' => $version,
            'appVersion' => $appVersion,
            'effectiveDate' => $effectiveDate,
            'googleAuthUrl' => $googleAuthUrl,
            'termsUrl' => $termsUrl,
            'versionsUrl' => $versionsUrl,
            'landingUrl' => $landingUrl,
        ]);
    }
}
