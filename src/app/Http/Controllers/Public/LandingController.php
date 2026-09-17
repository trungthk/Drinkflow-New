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
        return view('public.landing');
    }
}
