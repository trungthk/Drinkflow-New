<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\AdminStatus;
use App\Http\Requests\AdminLoginRequest;
use App\Mail\AdminResetPasswordOtpMail;
use App\Models\Admin;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    private const OTP_SEND_KEY_PREFIX = 'admin-reset-otp-send:';

    private const OTP_FAIL_KEY_PREFIX = 'admin-reset-otp-fail:';

    private const OTP_WINDOW_SECONDS = 900;

    private const OTP_MAX_SENDS = 5;

    private const OTP_MAX_FAILURES = 10;

    private const OTP_MAX_ATTEMPTS_PER_CODE = 5;

    /**
     * Authenticate an admin account with its credentials.
     *
     * The captcha is validated once, by the `captcha` rule of {@see AdminLoginRequest}. It must not be checked again here:
     * a captcha code is single-use, so a second check would always fail and lock every admin out.
     *
     * @param AdminLoginRequest $request Validated login request.
     * @return Admin Authenticated admin account instance.
     * @throws ValidationException If rate limited or the credentials are invalid.
     */
    public function login(AdminLoginRequest $request): Admin
    {
        $key = strtolower((string) $request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('admin.too_many_login_attempts'),
            ]);
        }

        $credentials = $request->only('email', 'password');
        $credentials['status'] = AdminStatus::Active->value;

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            SecurityEvent::create([
                'type' => 'failed_login',
                'severity' => 'medium',
                'ip_address' => $request->ip(),
                'metadata' => ['actor' => 'admin'],
            ]);

            throw ValidationException::withMessages([
                'email' => $this->inactiveAccountMessage((string) $request->input('email'), (string) $request->input('password'))
                    ?? __('admin.invalid_credentials'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $admin->update(['last_login_at' => now()]);

        return $admin;
    }

    /**
     * Explain why a correct password was refused for an account that cannot sign in yet.
     *
     * Only returned when the password is right, so it never reveals more than a successful login would.
     *
     * @param string $email Submitted email.
     * @param string $password Submitted password.
     * @return string|null Message for pending/rejected accounts, null otherwise (generic error).
     */
    private function inactiveAccountMessage(string $email, string $password): ?string
    {
        $admin = Admin::query()->where('email', mb_strtolower(trim($email)))->first();
        if ($admin === null || $admin->status->canSignIn() || ! Hash::check($password, (string) $admin->getAuthPassword())) {
            return null;
        }

        return match ($admin->status) {
            AdminStatus::Pending => $admin->hasVerifiedEmail()
                ? __('platform.registration.login_pending_review')
                : __('platform.registration.login_pending_verification'),
            AdminStatus::Rejected => __('platform.registration.login_rejected'),
            AdminStatus::Suspended => $admin->billing_suspended_at !== null ? __('platform.billing.login_suspended_billing') : null,
            default => null,
        };
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
     * The outcome is identical whether or not the account exists, so the endpoint cannot be used to enumerate administrators.
     *
     * @param string $email Admin account email.
     * @param string $ip Client IP address.
     * @return string Normalized email address.
     * @throws ValidationException If too many OTPs were requested for this email.
     */
    public function sendResetOtp(string $email, string $ip): string
    {
        $normalized = strtolower(trim($email));

        $sendKey = self::OTP_SEND_KEY_PREFIX.$normalized;
        if (RateLimiter::tooManyAttempts($sendKey, self::OTP_MAX_SENDS)) {
            throw ValidationException::withMessages([
                'email' => __('admin.too_many_login_attempts'),
            ]);
        }
        RateLimiter::hit($sendKey, self::OTP_WINDOW_SECONDS);

        $admin = Admin::where('email', $normalized)->where('status', AdminStatus::Active)->first();

        session()->forget(['admin_reset_otp', 'admin_reset_otp_expires_at', 'admin_reset_otp_attempts']);
        session()->put([
            'admin_reset_email' => $normalized,
            'admin_reset_verified' => false,
        ]);

        if ($admin) {
            $otp = (string) random_int(100000, 999999);
            session()->put([
                'admin_reset_otp' => $otp,
                'admin_reset_otp_expires_at' => now()->addMinutes(15)->timestamp,
                'admin_reset_otp_attempts' => 0,
            ]);

            try {
                Mail::to($normalized)->send(new AdminResetPasswordOtpMail($admin, $otp, 15));
            } catch (\Throwable $e) {
                Log::error('Failed to send admin password reset OTP email: ' . $e->getMessage(), [
                    'email' => $normalized,
                    'exception' => $e,
                ]);
            }
        }

        SecurityEvent::create([
            'type' => $admin ? 'password_reset_otp_requested' : 'password_reset_otp_unknown_account',
            'severity' => 'low',
            'ip_address' => $ip,
            'metadata' => ['email' => $normalized, 'actor' => 'admin'],
        ]);

        return $normalized;
    }

    /**
     * Verify the submitted OTP code against session state.
     *
     * Wrong guesses are counted per OTP (session) and per account (cache), so restarting the flow or opening
     * new sessions cannot be used to brute-force the 6-digit code.
     *
     * @param string $submittedOtp Submitted 6-digit OTP code.
     * @return void
     * @throws ValidationException If OTP is invalid, expired or the attempt limit was reached.
     */
    public function verifyResetOtp(string $submittedOtp): void
    {
        $email = (string) session()->get('admin_reset_email', '');
        $failKey = self::OTP_FAIL_KEY_PREFIX.$email;
        $invalid = ValidationException::withMessages([
            'otp' => __('admin.invalid_or_expired_otp'),
        ]);

        if ($email === '' || RateLimiter::tooManyAttempts($failKey, self::OTP_MAX_FAILURES)) {
            throw $invalid;
        }

        $sessionOtp = session()->get('admin_reset_otp');
        $expiresAt = (int) session()->get('admin_reset_otp_expires_at', 0);
        $attempts = (int) session()->get('admin_reset_otp_attempts', 0);

        if (! $sessionOtp || time() > $expiresAt || $attempts >= self::OTP_MAX_ATTEMPTS_PER_CODE) {
            throw $invalid;
        }

        if (! hash_equals((string) $sessionOtp, trim($submittedOtp))) {
            RateLimiter::hit($failKey, self::OTP_WINDOW_SECONDS);
            session()->put('admin_reset_otp_attempts', $attempts + 1);
            if ($attempts + 1 >= self::OTP_MAX_ATTEMPTS_PER_CODE) {
                session()->forget(['admin_reset_otp', 'admin_reset_otp_expires_at']);
            }

            throw $invalid;
        }

        RateLimiter::clear($failKey);
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
        $admin = Admin::where('email', $email)->where('status', AdminStatus::Active)->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'email' => __('admin.account_not_found'),
            ]);
        }

        // Changing the hash makes every other session of this admin fail the password fingerprint check;
        // rotating the remember token also revokes "remember me" cookies.
        $admin->forceFill(['remember_token' => Str::random(60)]);
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
            'admin_reset_otp_attempts',
            'admin_reset_verified',
        ]);
    }
}
