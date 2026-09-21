<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Enums\AdminStatus;
use App\Enums\GlobalUserStatus;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $service->ensureConfigured();

        return redirect()->away($service->getAuthorizationUrl($request));
    }

    /**
     * Xử lý callback trả về từ Google sau khi người dùng đồng ý ủy quyền đăng nhập.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa auth code và state
     * @param  \App\Services\Auth\GoogleOAuthService  $service  Service xử lý trao đổi token và xác thực người dùng
     * @return \Illuminate\Http\RedirectResponse  Phản hồi chuyển hướng người dùng về trang đích hoặc báo lỗi
     */
    public function callback(Request $request, GoogleOAuthService $service, AuditService $audit): RedirectResponse
    {
        $service->ensureConfigured();

        $storedLoginSource = $request->session()->pull('google_oauth_login_source');
        $storedLoginSource = is_string($storedLoginSource) ? $storedLoginSource : null;
        $loginSource = $service->isSafeInternalUrl($request, $storedLoginSource) ? $storedLoginSource : url('/');

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
            $pendingAdminId = $request->session()->get('admin_google_2fa_admin_id');
            if ($pendingAdminId) {
                $profile = $service->fetchProfile($request);
                $admin = AdminAccount::query()
                    ->whereKey((int) $pendingAdminId)
                    ->where('email', strtolower((string) $profile['email']))
                    ->where('status', AdminStatus::Active->value)
                    ->first();

                if (! $admin) {
                    throw ValidationException::withMessages([
                        'email' => __('admin.google_workspace_identity_mismatch'),
                    ]);
                }

                $remember = (bool) $request->session()->get('admin_google_2fa_remember', false);
                \Illuminate\Support\Facades\Auth::guard('admin')->login($admin, $remember);
                $request->session()->regenerate();
                $request->session()->forget([
                    'admin_google_2fa_admin_id',
                    'admin_google_2fa_remember',
                ]);
                $admin->update(['last_login_at' => now()]);
                $audit->record('admin.logged_in', 'admin', $admin->id, null, [], [], [
                    'authentication_method' => 'google_oauth_2fa',
                ]);

                return redirect()->route('admin.landing');
            }

            $user = $service->handleCallback($request);

            $globalStatus = $user->status instanceof GlobalUserStatus
                ? $user->status
                : GlobalUserStatus::tryFrom((string) $user->status);
            if ($globalStatus !== GlobalUserStatus::Active) {
                throw ValidationException::withMessages([
                    'email' => __('global.auth.google_account_access_revoked'),
                ]);
            }

            \Illuminate\Support\Facades\Auth::guard('web')->login($user, true);
            $request->session()->regenerate();
            $audit->record('user.logged_in', 'global_user', $user->id, null, [], [], [
                'authentication_method' => 'google_oauth',
            ]);

            $intended = $request->session()->pull('url.intended');
            if ($service->isSafeInternalUrl($request, $intended)) {
                return redirect()->to($intended)
                    ->withoutCookie('drinkflow_device_uuid')
                    ->withoutCookie('drinkflow_trusted_token');
            }

            return redirect()->route('user.me.dashboard')
                ->withoutCookie('drinkflow_device_uuid')
                ->withoutCookie('drinkflow_trusted_token');
        } catch (ValidationException $e) {
            $errorMessage = $e->errors()['email'][0] ?? $e->getMessage() ?? __('global.auth.google_unsupported_account');
            return redirect()->to($loginSource)
                ->with('login_error', $errorMessage)
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::warning('Google OAuth login failed.', ['exception' => $e::class, 'error' => $e->getMessage()]);

            return redirect()->to($loginSource)
                ->with('login_error', __('global.auth.google_login_failed'));
        }
    }
}
