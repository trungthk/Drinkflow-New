<?php

declare(strict_types=1);

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
     *
     * @return View Rendered contact page view.
     */
    public function index(): View
    {
        return view('public.contact');
    }

    /**
     * Store an incoming enterprise contact inquiry.
     *
     * @param ContactRequest $request Validated contact form request.
     * @param ContactService $contactService Service processing the inquiry creation.
     * @return JsonResponse|RedirectResponse JSON response or redirect with flash ticket.
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
