<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\SuperadminStatus;
use App\Http\Requests\SuperadminLoginRequest;
use App\Models\SecurityEvent;
use App\Models\Superadmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Session authentication for platform Superadmins on the dedicated `superadmin` guard.
 */
class SuperadminAuthService
{
    /** Failed attempts allowed per email and IP before the form is locked for a minute. */
    private const MAX_ATTEMPTS = 5;

    /**
     * Sign an active Superadmin in.
     *
     * Accounts with two-factor authentication enabled are refused: the second factor is not
     * available on the superadmin guard yet, and signing in without it must not be possible.
     *
     * @param SuperadminLoginRequest $request Validated login request.
     * @return Superadmin Authenticated superadmin.
     * @throws ValidationException When rate limited, the credentials are invalid, or 2FA is required.
     */
    public function login(SuperadminLoginRequest $request): Superadmin
    {
        $key = 'superadmin|'.strtolower((string) $request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages(['email' => __('admin.too_many_login_attempts')]);
        }

        $credentials = $request->only('email', 'password');
        $credentials['status'] = SuperadminStatus::Active->value;

        $guard = Auth::guard('superadmin');
        if (! $guard->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            SecurityEvent::create([
                'type' => 'failed_login',
                'severity' => 'high',
                'ip_address' => $request->ip(),
                'metadata' => ['actor' => 'superadmin'],
            ]);

            throw ValidationException::withMessages(['email' => __('admin.invalid_credentials')]);
        }

        /** @var Superadmin $superadmin */
        $superadmin = $guard->user();
        if ($superadmin->two_factor_enabled) {
            $guard->logout();
            throw ValidationException::withMessages(['email' => __('superadmin.auth.two_factor_unavailable')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $superadmin->update(['last_login_at' => now()]);

        return $superadmin;
    }

    /**
     * Sign the Superadmin out and discard the session.
     *
     * @param Request $request Incoming request.
     * @return void
     */
    public function logout(Request $request): void
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
