<?php

namespace App\Services\Auth;

use App\Models\GlobalUser;
use App\Models\OAuthIdentity;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoogleOAuthService
{
    /**
     * Handle the validate profile operation.
     * @param array $profile Parameter value.
     * @return void Result of the operation.
     */
    public function validateProfile(array $profile): void
    {
        if (($profile['email_verified'] ?? false) !== true) {
            SecurityEvent::create([
                'type' => 'google_oauth_failure',
                'severity' => 'medium',
                'metadata' => ['reason' => 'unverified_email'],
            ]);
            throw ValidationException::withMessages(['email' => 'Email Google chưa được xác minh.']);
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
                ? "Tài khoản có domain 'google.com' không hỗ trợ đăng nhập. Vui lòng sử dụng tài khoản email doanh nghiệp được cấp phép."
                : ($domain
                    ? "Tài khoản có domain '{$domain}' không hỗ trợ đăng nhập. Vui lòng sử dụng tài khoản email doanh nghiệp được cấp phép."
                    : 'Email không hợp lệ.');

            throw ValidationException::withMessages(['email' => $message]);
        }

        if (empty($profile['sub'])) {
            SecurityEvent::create([
                'type' => 'google_oauth_failure',
                'severity' => 'high',
                'metadata' => ['reason' => 'missing_subject'],
            ]);
            throw ValidationException::withMessages(['email' => 'Google identity không hợp lệ.']);
        }
    }

    /**
     * Handle the resolve user operation.
     * @param array $profile Parameter value.
     * @return GlobalUser Result of the operation.
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
                    'status' => 'active',
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
     * Handle the normalize operation.
     * @param string $name Parameter value.
     * @return string Result of the operation.
     */
    private function normalize(string $name): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name)));
    }
}
