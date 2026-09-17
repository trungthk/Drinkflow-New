<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
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
        return view('public.terms', [
            'docVersion' => 'v1.0',
            'effectiveDate' => FormatHelper::formatDate('2026-09-10'),
        ]);
    }
}
