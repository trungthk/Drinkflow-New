<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    /**
     * Handle the redirect operation.
     * @param Request $request Parameter value.
     * @return RedirectResponse Result of the operation.
     */
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(config('services.google.client_id'), 503, 'Google authentication is not configured.');

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        // Store origin page where user started login to redirect back gracefully on error
        $loginSource = $request->header('referer') ?? url('/');
        $request->session()->put('google_oauth_login_source', $loginSource);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Handle the callback operation.
     * @param Request $request Parameter value.
     * @param GoogleOAuthService $service Parameter value.
     * @return RedirectResponse Result of the operation.
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
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $request->string('code')->toString(),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

            if ($tokenResponse->failed()) {
                return redirect()->to($loginSource)->with('login_error', __('global.auth.google_token_failed'));
            }

            $token = $tokenResponse->json();
            $profileResponse = Http::withToken($token['access_token'] ?? '')
                ->get('https://openidconnect.googleapis.com/v1/userinfo');

            if ($profileResponse->failed()) {
                return redirect()->to($loginSource)->with('login_error', __('global.auth.google_profile_failed'));
            }

            $profile = $profileResponse->json();

            $user = $service->resolveUser([
                'sub' => $profile['sub'] ?? null,
                'email' => $profile['email'] ?? null,
                'name' => $profile['name'] ?? '',
                'picture' => $profile['picture'] ?? null,
                'email_verified' => (bool) ($profile['email_verified'] ?? false),
            ]);

            auth('web')->login($user, true);
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
