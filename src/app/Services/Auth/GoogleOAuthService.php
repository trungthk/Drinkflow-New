<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\GlobalUser;
use App\Models\OAuthIdentity;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleOAuthService
{
    /**
     * Tạo đường dẫn cấp quyền xác thực Google OAuth và lưu state vào session.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @return string  URL chuyển hướng đến trang đăng nhập Google
     */
    public function getAuthorizationUrl(Request $request): string
    {
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

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

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    /**
     * Trao đổi authorization code lấy thông tin hồ sơ Google và xác thực/tạo tài khoản GlobalUser.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request chứa mã code trả về từ Google
     * @return \App\Models\GlobalUser  Tài khoản người dùng toàn hệ thống đã được xác thực
     * @throws \RuntimeException  Khi không lấy được access token hoặc thông tin hồ sơ
     * @throws \Illuminate\Validation\ValidationException  Khi email chưa xác thực, domain không được phép hoặc thiếu sub
     */
    public function handleCallback(Request $request): GlobalUser
    {
        return $this->resolveUser($this->fetchProfile($request));
    }

    /**
     * Exchange the OAuth callback code for a validated Google profile.
     *
     * @param Request $request OAuth callback request.
     * @return array<string, mixed> Validated Google profile.
     * @throws \RuntimeException When Google rejects the token or profile request.
     * @throws ValidationException When the Google identity is not permitted.
     */
    public function fetchProfile(Request $request): array
    {
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $request->string('code')->toString(),
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if ($tokenResponse->failed()) {
            throw new \RuntimeException(__('global.auth.google_token_failed'));
        }

        $token = $tokenResponse->json();
        $profileResponse = Http::withToken($token['access_token'] ?? '')
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if ($profileResponse->failed()) {
            throw new \RuntimeException(__('global.auth.google_profile_failed'));
        }

        $profile = $profileResponse->json();

        $profile = [
            'sub' => $profile['sub'] ?? null,
            'email' => $profile['email'] ?? null,
            'name' => $profile['name'] ?? '',
            'picture' => $profile['picture'] ?? null,
            'email_verified' => (bool) ($profile['email_verified'] ?? false),
        ];

        $this->validateProfile($profile);

        return $profile;
    }

    /**
     * Kiểm tra tính hợp lệ của thông tin hồ sơ nhận từ Google (email verified, whitelist domains, subject id).
     *
     * @param  array<string, mixed>  $profile  Dữ liệu hồ sơ người dùng từ Google
     * @return void
     * @throws \Illuminate\Validation\ValidationException  Khi dữ liệu hồ sơ vi phạm chính sách bảo mật
     */
    public function validateProfile(array $profile): void
    {
        if (($profile['email_verified'] ?? false) !== true) {
            SecurityEvent::create([
                'type' => 'google_oauth_failure',
                'severity' => 'medium',
                'metadata' => ['reason' => 'unverified_email'],
            ]);
            throw ValidationException::withMessages(['email' => __('public.auth_modal.error_unverified_email')]);
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $domain = str_contains($email, '@') ? substr($email, strrpos($email, '@') + 1) : '';
        $allowed = config('services.google.allowed_domains', []);

        if ($domain === 'google.com' || $email === '' || ($allowed !== [] && !in_array($domain, $allowed, true))) {
            SecurityEvent::create([
                'type' => 'invalid_company_domain',
                'severity' => 'high',
                'metadata' => ['domain' => $domain],
            ]);

            $message = $domain === 'google.com'
                ? __('public.auth_modal.error_domain_unsupported', ['domain' => 'google.com'])
                : ($domain
                    ? __('public.auth_modal.error_domain_unsupported', ['domain' => $domain])
                    : __('public.auth_modal.error_email_invalid'));

            throw ValidationException::withMessages(['email' => $message]);
        }

        if (empty($profile['sub'])) {
            SecurityEvent::create([
                'type' => 'google_oauth_failure',
                'severity' => 'high',
                'metadata' => ['reason' => 'missing_subject'],
            ]);
            throw ValidationException::withMessages(['email' => __('public.auth_modal.error_invalid_identity')]);
        }
    }

    /**
     * Tìm hoặc khởi tạo tài khoản GlobalUser và liên kết với danh tính OAuthIdentity tương ứng.
     *
     * @param  array<string, mixed>  $profile  Thông tin hồ sơ Google đã qua kiểm duyệt
     * @return \App\Models\GlobalUser  Tài khoản người dùng toàn hệ thống
     * @throws \Illuminate\Validation\ValidationException  Khi identity đã gắn với người dùng khác
     */
    public function resolveUser(array $profile): GlobalUser
    {
        $this->validateProfile($profile);

        return DB::transaction(function () use ($profile): GlobalUser {
            $identity = OAuthIdentity::query()
                ->where('provider', 'google')
                ->where('provider_user_id', $profile['sub'])
                ->first();

            $emailUser = GlobalUser::query()
                ->where('email', strtolower($profile['email']))
                ->first();

            if ($identity && $emailUser && $identity->global_user_id !== $emailUser->id) {
                throw ValidationException::withMessages(['email' => 'Google identity đã liên kết với tài khoản khác.']);
            }

            $user = $identity?->globalUser ?: $emailUser;

            if (!$user) {
                $user = GlobalUser::create([
                    'name' => $profile['name'],
                    'normalized_name' => $this->normalize($profile['name']),
                    'email' => strtolower($profile['email']),
                    'avatar_url' => $profile['picture'] ?? null,
                    'status' => \App\Enums\GlobalUserStatus::Active->value,
                ]);
            }

            $identity = OAuthIdentity::firstOrCreate(
                ['provider' => 'google', 'provider_user_id' => $profile['sub']],
                ['global_user_id' => $user->id, 'provider_email' => strtolower($profile['email']), 'linked_at' => now()]
            );

            $user->update([
                'name' => (string) ($profile['name'] ?? $user->name),
                'normalized_name' => $this->normalize((string) ($profile['name'] ?? $user->name)),
                'email' => strtolower($profile['email']),
                'avatar_url' => $profile['picture'] ?? $user->avatar_url,
                'last_login_at' => now(),
            ]);

            $identity->update([
                'provider_email' => strtolower($profile['email']),
                'last_login_at' => now(),
            ]);

            return $user->fresh();
        });
    }

    /**
     * Chuẩn hóa chuỗi họ tên người dùng thành không dấu để hỗ trợ tìm kiếm nhanh.
     *
     * @param  string  $name  Họ tên người dùng
     * @return string  Tên đã được chuyển thành chữ thường không dấu
     */
    private function normalize(string $name): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name)));
    }
}
