<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Models\SecurityEvent;

class AuthController extends Controller
{
    public function loginPage(Request $request): View|RedirectResponse
    {
        if ($request->user('admin')) return redirect()->route('admin.landing');
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $request->session()->put(['admin_captcha_question' => "$a + $b = ?", 'admin_captcha_answer' => (string) ($a + $b)]);
        return view('admin.login', ['captchaQuestion' => "$a + $b = ?"]);
    }

    public function landing(Request $request): View|RedirectResponse
    {
        if ($request->user('admin')->isSuperadmin()) return redirect()->route('superadmin.dashboard');
        $rooms = $request->user('admin')->rooms()->where('status', 'active')->orderBy('name')->get();
        if ($rooms->count() === 1) return redirect()->route('admin.dashboard.page', $rooms->first());
        return view('admin.rooms', ['rooms' => $rooms, 'admin' => $request->user('admin')]);
    }

    public function login(AdminLoginRequest $request): JsonResponse|RedirectResponse
    {
        $key = strtolower($request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['email' => 'Quá nhiều lần đăng nhập thất bại.']);
        $answer = $request->session()->pull('admin_captcha_answer');
        if ($answer !== null && ! hash_equals((string) $answer, trim((string) $request->input('captcha')))) {
            RateLimiter::hit($key, 60);
            SecurityEvent::create(['type' => 'failed_login', 'severity' => 'medium', 'ip_address' => $request->ip(), 'metadata' => ['actor' => 'admin', 'reason' => 'captcha']]);
            throw ValidationException::withMessages(['captcha' => 'Captcha không đúng.']);
        }
        $credentials = $request->only('email', 'password');
        $credentials['status'] = 'active';
        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            SecurityEvent::create(['type' => 'failed_login', 'severity' => 'medium', 'ip_address' => $request->ip(), 'metadata' => ['actor' => 'admin']]);
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không hợp lệ.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user('admin')->update(['last_login_at' => now()]);
        if ($request->expectsJson()) return response()->json(['data' => Auth::guard('admin')->user()]);
        return redirect()->intended(route('admin.landing'));
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($request->expectsJson()) return response()->json(['message' => 'logged_out']);
        return redirect()->route('admin.login.page');
    }
}
