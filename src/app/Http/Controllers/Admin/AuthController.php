<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendResetOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\AdminAccount;
use App\Services\Auth\AdminAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Display the Admin login page with captcha challenge.
     *
     * @param Request $request Incoming HTTP request.
     * @param AdminAuthService $authService Admin authentication service.
     * @return View|RedirectResponse Login view or redirect if already authenticated.
     */
    public function loginPage(Request $request, AdminAuthService $authService): View|RedirectResponse
    {
        if ($request->user('admin')) {
            return redirect()->route('admin.landing');
        }

        $question = $authService->generateCaptcha($request);

        return view('admin.auth.login', ['captchaQuestion' => $question]);
    }

    /**
     * Handle the admin landing dispatch (switch room or redirect to dashboard).
     *
     * @param Request $request Incoming HTTP request.
     * @return View|RedirectResponse Admin rooms list or direct room dashboard.
     */
    public function landing(Request $request): View|RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');

        if ($admin->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        $rooms = $admin->rooms()->where('status', RoomStatus::Active)->orderBy('name')->get();

        if ($rooms->count() === 1) {
            return redirect()->route('admin.dashboard.page', $rooms->first());
        }

        return view('admin.rooms', ['rooms' => $rooms, 'admin' => $admin]);
    }

    /**
     * Authenticate admin account credentials.
     *
     * @param AdminLoginRequest $request Validated admin login request.
     * @param AdminAuthService $authService Authentication service.
     * @return JsonResponse|RedirectResponse Response containing admin payload or redirect.
     */
    public function login(AdminLoginRequest $request, AdminAuthService $authService): JsonResponse|RedirectResponse
    {
        $admin = $authService->login($request);

        if ($request->expectsJson()) {
            return response()->json(['data' => $admin]);
        }

        return redirect()->intended(route('admin.landing'));
    }

    /**
     * Terminate the admin authenticated session.
     *
     * @param Request $request Incoming HTTP request.
     * @param AdminAuthService $authService Authentication service.
     * @return JsonResponse|RedirectResponse Logout response.
     */
    public function logout(Request $request, AdminAuthService $authService): JsonResponse|RedirectResponse
    {
        $authService->logout($request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'logged_out']);
        }

        return redirect()->route('admin.login.page');
    }

    /**
     * Display the forgot password request view.
     *
     * @param Request $request Incoming HTTP request.
     * @return View View instance.
     */
    public function forgotPasswordPage(Request $request): View
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Send password reset OTP challenge to admin email.
     *
     * @param SendResetOtpRequest $request Validated OTP dispatch request.
     * @param AdminAuthService $authService Authentication service.
     * @return JsonResponse|RedirectResponse Redirection to verify OTP view or JSON payload.
     */
    public function sendResetOtp(SendResetOtpRequest $request, AdminAuthService $authService): JsonResponse|RedirectResponse
    {
        $email = $authService->sendResetOtp((string) $request->input('email'), $request->ip() ?? '127.0.0.1');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP dispatched successfully',
                'email' => $email,
            ]);
        }

        return redirect()->route('admin.verify-otp.page')->with('status', __('admin.otp_dispatched'));
    }

    /**
     * Display the OTP verification view.
     *
     * @param Request $request Incoming HTTP request.
     * @return View|RedirectResponse Verify view or redirect back if no reset in progress.
     */
    public function verifyOtpPage(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('admin_reset_email');
        if (! $email) {
            return redirect()->route('admin.forgot-password.page');
        }

        return view('admin.auth.verify-otp', ['email' => $email]);
    }

    /**
     * Verify the submitted OTP code.
     *
     * @param VerifyOtpRequest $request Validated OTP input request.
     * @param AdminAuthService $authService Authentication service.
     * @return JsonResponse|RedirectResponse Redirect to reset password or JSON.
     */
    public function verifyOtp(VerifyOtpRequest $request, AdminAuthService $authService): JsonResponse|RedirectResponse
    {
        $authService->verifyResetOtp((string) $request->input('otp'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'OTP verified']);
        }

        return redirect()->route('admin.reset-password.page');
    }

    /**
     * Display the new password reset input form.
     *
     * @param Request $request Incoming HTTP request.
     * @return View|RedirectResponse Reset view or redirect if not verified.
     */
    public function resetPasswordPage(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('admin_reset_verified')) {
            return redirect()->route('admin.forgot-password.page');
        }

        return view('admin.auth.reset-password', [
            'email' => $request->session()->get('admin_reset_email'),
        ]);
    }

    /**
     * Update the admin account password after successful OTP verification.
     *
     * @param ResetPasswordRequest $request Validated password reset request.
     * @param AdminAuthService $authService Authentication service.
     * @return JsonResponse|RedirectResponse Redirect to login page with success.
     */
    public function resetPassword(ResetPasswordRequest $request, AdminAuthService $authService): JsonResponse|RedirectResponse
    {
        if (! $request->session()->get('admin_reset_verified')) {
            return redirect()->route('admin.forgot-password.page');
        }

        $authService->resetPassword((string) $request->input('password'), $request->ip() ?? '127.0.0.1');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password reset successfully']);
        }

        return redirect()->route('admin.login.page')->with('status', __('admin.password_reset_success'));
    }
}

