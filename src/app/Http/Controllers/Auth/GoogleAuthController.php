<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    /**
     * Điều hướng người dùng sang trang xác thực Google OAuth 2.0.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Services\Auth\GoogleOAuthService  $service  Service điều phối xác thực Google OAuth
     * @return \Illuminate\Http\RedirectResponse  Phản hồi chuyển hướng sang Google
     */
    public function redirect(Request $request, GoogleOAuthService $service): RedirectResponse
    {
        abort_unless(config('services.google.client_id'), 503, 'Google authentication is not configured.');

        return redirect()->away($service->getAuthorizationUrl($request));
    }

    /**
     * Xử lý callback trả về từ Google sau khi người dùng đồng ý ủy quyền đăng nhập.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa auth code và state
     * @param  \App\Services\Auth\GoogleOAuthService  $service  Service xử lý trao đổi token và xác thực người dùng
     * @return \Illuminate\Http\RedirectResponse  Phản hồi chuyển hướng người dùng về trang đích hoặc báo lỗi
     */
    public function callback(Request $request, GoogleOAuthService $service): RedirectResponse
    {
        $loginSource = $request->session()->pull('google_oauth_login_source') ?: url('/');

        // 1. Handle error returned by Google (e.g. user canceled)
        if ($request->has('error')) {
            return redirect()->to($loginSource)->with('login_error', __('global.auth.google_cancelled'));
        }

        // 2. Validate state & code
        $savedState = (string) $request->session()->pull('google_oauth_state');
        if (!$request->filled('code') || !hash_equals($savedState, (string) $request->input('state'))) {
            return redirect()->to($loginSource)->with('login_error', __('global.auth.google_session_expired'));
        }

        try {
            $user = $service->handleCallback($request);

            \Illuminate\Support\Facades\Auth::guard('web')->login($user, true);
            $request->session()->regenerate();

            $intended = $request->session()->pull('url.intended');
            if ($intended && !Str::contains($intended, ['accounts.google.com', 'google.com'])) {
                return redirect()->to($intended);
            }

            return redirect()->route('user.me.dashboard');
        } catch (ValidationException $e) {
            $errorMessage = $e->errors()['email'][0] ?? $e->getMessage() ?? __('global.auth.google_unsupported_account');
            return redirect()->to($loginSource)
                ->with('login_error', $errorMessage)
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            return redirect()->to($loginSource)
                ->with('login_error', __('global.auth.google_login_failed', ['error' => $e->getMessage()]));
        }
    }
}
