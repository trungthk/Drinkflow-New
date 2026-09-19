<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\AdminStatus;
use App\Http\Requests\AdminLoginRequest;
use App\Mail\AdminResetPasswordOtpMail;
use App\Models\AdminAccount;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    /**
     * Authenticate an admin account with credentials and captcha validation.
     *
     * @param AdminLoginRequest $request Validated login request.
     * @return AdminAccount Authenticated admin account instance.
     * @throws ValidationException If rate limited, invalid captcha, or bad credentials.
     */
    public function login(AdminLoginRequest $request): AdminAccount
    {
        $key = strtolower((string) $request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('admin.too_many_login_attempts'),
            ]);
        }

        // Validate Captcha when not running in local environment or testing
        $isLocalOrDisabled = app()->isLocal() || (bool) config('captcha.disable');
        if (! $isLocalOrDisabled) {
            $captchaInput = (string) $request->input('captcha', '');
            if ($captchaInput === '' || ! function_exists('captcha_check') || ! captcha_check($captchaInput)) {
                RateLimiter::hit($key, 60);
                SecurityEvent::create([
                    'type' => 'failed_login',
                    'severity' => 'medium',
                    'ip_address' => $request->ip(),
                    'metadata' => ['actor' => 'admin', 'reason' => 'captcha'],
                ]);

                throw ValidationException::withMessages([
                    'captcha' => __('admin.invalid_captcha'),
                ]);
            }
        }

        $credentials = $request->only('email', 'password');
        $credentials['status'] = 'active';

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            SecurityEvent::create([
                'type' => 'failed_login',
                'severity' => 'medium',
                'ip_address' => $request->ip(),
                'metadata' => ['actor' => 'admin'],
            ]);

            throw ValidationException::withMessages([
                'email' => __('admin.invalid_credentials'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var AdminAccount $admin */
        $admin = Auth::guard('admin')->user();
        $admin->update(['last_login_at' => now()]);

        return $admin;
    }

    /**
     * Discard admin session and log out.
     *
     * @param Request $request Incoming HTTP request.
     * @return void
     */
    public function logout(Request $request): void
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Dispatch a 6-digit password reset OTP to admin email.
     *
     * @param string $email Admin account email.
     * @param string $ip Client IP address.
     * @return string Normalized email address.
     * @throws ValidationException If account not found or inactive.
     */
    public function sendResetOtp(string $email, string $ip): string
    {
        $normalized = strtolower(trim($email));
        $admin = AdminAccount::where('email', $normalized)->where('status', AdminStatus::Active)->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'email' => __('admin.account_not_found'),
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        session()->put([
            'admin_reset_email' => $normalized,
            'admin_reset_otp' => $otp,
            'admin_reset_otp_expires_at' => now()->addMinutes(15)->timestamp,
            'admin_reset_verified' => false,
        ]);

        try {
            Mail::to($normalized)->send(new AdminResetPasswordOtpMail($admin, $otp, 15));
        } catch (\Throwable $e) {
            Log::error('Failed to send admin password reset OTP email: ' . $e->getMessage(), [
                'email' => $normalized,
                'exception' => $e,
            ]);
        }

        SecurityEvent::create([
            'type' => 'password_reset_otp_requested',
            'severity' => 'low',
            'ip_address' => $ip,
            'metadata' => ['email' => $normalized, 'actor' => 'admin'],
        ]);

        return $normalized;
    }

    /**
     * Verify the submitted OTP code against session state.
     *
     * @param string $submittedOtp Submitted 6-digit OTP code.
     * @return void
     * @throws ValidationException If OTP is invalid or expired.
     */
    public function verifyResetOtp(string $submittedOtp): void
    {
        $sessionOtp = session()->get('admin_reset_otp');
        $expiresAt = (int) session()->get('admin_reset_otp_expires_at', 0);

        if (! $sessionOtp || time() > $expiresAt || ! hash_equals((string) $sessionOtp, trim($submittedOtp))) {
            throw ValidationException::withMessages([
                'otp' => __('admin.invalid_or_expired_otp'),
            ]);
        }

        session()->put('admin_reset_verified', true);
    }

    /**
     * Complete the password reset procedure.
     *
     * @param string $password New plain password.
     * @param string $ip Client IP address.
     * @return void
     * @throws ValidationException If session state invalid or account not found.
     */
    public function resetPassword(string $password, string $ip): void
    {
        $email = session()->get('admin_reset_email');
        $admin = AdminAccount::where('email', $email)->where('status', AdminStatus::Active)->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'email' => __('admin.account_not_found'),
            ]);
        }

        $admin->update([
            'password' => Hash::make($password),
        ]);

        SecurityEvent::create([
            'type' => 'password_reset_completed',
            'severity' => 'medium',
            'ip_address' => $ip,
            'metadata' => ['email' => $email, 'actor' => 'admin'],
        ]);

        session()->forget([
            'admin_reset_email',
            'admin_reset_otp',
            'admin_reset_otp_expires_at',
            'admin_reset_verified',
        ]);
    }
}
