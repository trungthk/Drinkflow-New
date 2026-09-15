<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function callback(Request $request, GoogleOAuthService $service, AuditService $audit): RedirectResponse
    {
        $storedLoginSource = $request->session()->pull('google_oauth_login_source');
        $storedLoginSource = is_string($storedLoginSource) ? $storedLoginSource : null;
        $loginSource = $this->isSafeInternalUrl($request, $storedLoginSource) ? $storedLoginSource : url('/');

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
            $pendingAdminId = $request->session()->pull('admin_google_2fa_admin_id');
            if ($pendingAdminId) {
                $profile = $service->fetchProfile($request);
                $admin = AdminAccount::query()
                    ->whereKey((int) $pendingAdminId)
                    ->where('email', strtolower((string) $profile['email']))
                    ->where('status', 'active')
                    ->first();

                if (! $admin) {
                    throw ValidationException::withMessages([
                        'email' => __('admin.google_workspace_identity_mismatch'),
                    ]);
                }

                \Illuminate\Support\Facades\Auth::guard('admin')->login($admin, true);
                $request->session()->regenerate();
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
            $hasMemberships = $user->roomUsers()->exists();
            $hasActiveMembership = $user->roomUsers()->where('status', RoomUserStatus::Active->value)->exists();

            if ($globalStatus !== GlobalUserStatus::Active || ($hasMemberships && ! $hasActiveMembership)) {
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
            if ($this->isSafeInternalUrl($request, $intended)) {
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

    /**
     * Determine whether an OAuth return URL belongs to the current application.
     *
     * @param Request $request Current HTTP request.
     * @param string|null $url Candidate return URL.
     * @return bool True when the URL is relative or matches the current host.
     */
    private function isSafeInternalUrl(Request $request, ?string $url): bool
    {
        if ($url === null || $url === '' || str_starts_with($url, '//')) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        if (! isset($parts['host'])) {
            return str_starts_with($url, '/');
        }

        $host = strtolower((string) $parts['host']);
        $requestHost = strtolower($request->getHost());
        return $host === $requestHost
            || ($host === 'localhost' && $requestHost === '127.0.0.1')
            || ($host === '127.0.0.1' && $requestHost === 'localhost');
    }
}
