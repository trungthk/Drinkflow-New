<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperadminLoginRequest;
use App\Services\Audit\AuditService;
use App\Services\Auth\SuperadminAuthService;
use App\Support\Auth\AreaIntendedRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sign-in and sign-out for the `superadmin` guard (/superadmin/login).
 */
class AuthController extends Controller
{
    /**
     * Show the superadmin login form, or go to the dashboard when already signed in.
     *
     * @param Request $request Incoming request.
     * @return View|RedirectResponse Login form or dashboard redirect.
     */
    public function loginPage(Request $request): View|RedirectResponse
    {
        if ($request->user('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('admin.auth.login', ['superadminLogin' => true]);
    }

    /**
     * Authenticate a superadmin with email and password.
     *
     * @param SuperadminLoginRequest $request Validated credentials.
     * @param SuperadminAuthService $auth Authentication service.
     * @param AuditService $audit Audit log service.
     * @return RedirectResponse Intended superadmin page.
     */
    public function login(SuperadminLoginRequest $request, SuperadminAuthService $auth, AuditService $audit): RedirectResponse
    {
        $superadmin = $auth->login($request);
        $audit->record('superadmin.logged_in', 'superadmin', $superadmin->id, null, [], [], ['authentication_method' => 'password']);

        return AreaIntendedRedirect::to($request, 'superadmin', route('superadmin.dashboard'));
    }

    /**
     * Sign the superadmin out.
     *
     * @param Request $request Incoming request.
     * @param SuperadminAuthService $auth Authentication service.
     * @return RedirectResponse Login page.
     */
    public function logout(Request $request, SuperadminAuthService $auth): RedirectResponse
    {
        $auth->logout($request);

        return redirect()->route('superadmin.login.page');
    }
}
