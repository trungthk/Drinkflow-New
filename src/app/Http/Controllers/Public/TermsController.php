<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Version;
use App\Support\Helpers\FormatHelper;
use Illuminate\Contracts\View\View;

class TermsController extends Controller
{
    /**
     * Handle the incoming request to render the terms and conditions page.
     *
     * @return View Terms of service view response.
     */
    public function __invoke(): View
    {
        $version = 'v1.0';
        $appVersion = Version::getLatestVersionString();
        $effectiveDate = FormatHelper::formatDate('2026-09-10');
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
