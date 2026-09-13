<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\Contact\ContactService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    /**
     * Show the enterprise contact page.
     */
    public function index(): View
    {
        $appVersion = config('app.version', 'v2.3.0');
        $googleAuthUrl = route('auth.google');
        $termsUrl = route('terms');
        $versionsUrl = route('versions');
        $landingUrl = route('landing');
        $contactUrl = route('contact');

        return view('public.contact', [
            'appVersion' => $appVersion,
            'googleAuthUrl' => $googleAuthUrl,
            'termsUrl' => $termsUrl,
            'versionsUrl' => $versionsUrl,
            'landingUrl' => $landingUrl,
            'contactUrl' => $contactUrl,
        ]);
    }

    /**
     * Store an incoming enterprise contact inquiry.
     */
    public function store(ContactRequest $request, ContactService $contactService): JsonResponse|RedirectResponse
    {
        $inquiry = $contactService->createInquiry(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ticket_code' => $inquiry->ticket_code,
                'message' => __('contact.modal.desc', ['ticket' => $inquiry->ticket_code]),
            ]);
        }

        return redirect()->route('contact')->with('success_ticket', $inquiry->ticket_code);
    }
}
