<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(config('services.google.client_id'), 503, 'Google authentication is not configured.');
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $query = http_build_query(['client_id' => config('services.google.client_id'), 'redirect_uri' => config('services.google.redirect'), 'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state, 'access_type' => 'online', 'prompt' => 'select_account']);
        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request, GoogleOAuthService $service): RedirectResponse
    {
        abort_unless($request->filled('code') && hash_equals((string) $request->session()->pull('google_oauth_state'), (string) $request->input('state')), 419);
        $token = Http::asForm()->post('https://oauth2.googleapis.com/token', ['code' => $request->string('code')->toString(), 'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'), 'redirect_uri' => config('services.google.redirect'), 'grant_type' => 'authorization_code'])->throw()->json();
        $profile = Http::withToken($token['access_token'] ?? '')->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        $user = $service->resolveUser(['sub' => $profile['sub'] ?? null, 'email' => $profile['email'] ?? null, 'name' => $profile['name'] ?? '', 'picture' => $profile['picture'] ?? null, 'email_verified' => (bool) ($profile['email_verified'] ?? false)]);
        auth('web')->login($user, true);
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
}
