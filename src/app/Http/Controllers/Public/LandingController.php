<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * Handle the incoming request to render the public landing page.
     *
     * @return View Landing page view response.
     */
    public function __invoke(): View
    {
        $version = (string) config('app.version', 'v2.3.0');
        $googleAuthUrl = route('auth.google');
        $termsUrl = url('/terms');
        $versionsUrl = url('/versions');

        return view('public.landing', [
            'version' => $version,
            'googleAuthUrl' => $googleAuthUrl,
            'termsUrl' => $termsUrl,
            'versionsUrl' => $versionsUrl,
        ]);
    }
}
