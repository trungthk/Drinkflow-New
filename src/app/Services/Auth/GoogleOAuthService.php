<?php
namespace App\Services\Auth;

use App\Models\GlobalUser;
use App\Models\OAuthIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoogleOAuthService
{
    public function validateProfile(array $profile): void
    {
        if (($profile['email_verified'] ?? false) !== true) throw ValidationException::withMessages(['email' => 'Email Google chưa được xác minh.']);
        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $domain = str_contains($email, '@') ? substr($email, strrpos($email, '@') + 1) : '';
        $allowed = config('services.google.allowed_domains', []);
        if ($email === '' || ($allowed !== [] && !in_array($domain, $allowed, true))) throw ValidationException::withMessages(['email' => 'Email không thuộc domain công ty được phép.']);
        if (empty($profile['sub'])) throw ValidationException::withMessages(['email' => 'Google identity không hợp lệ.']);
    }

    public function resolveUser(array $profile): GlobalUser
    {
        $this->validateProfile($profile);
        return DB::transaction(function () use ($profile): GlobalUser {
            $identity = OAuthIdentity::query()->where('provider', 'google')->where('provider_user_id', $profile['sub'])->first();
            $user = $identity?->globalUser;
            if (!$user) $user = GlobalUser::query()->where('email', strtolower($profile['email']))->first();
            if (!$user) $user = GlobalUser::create(['name' => $profile['name'], 'normalized_name' => $this->normalize($profile['name']), 'email' => strtolower($profile['email']), 'avatar_url' => $profile['picture'] ?? null, 'status' => 'active']);
            OAuthIdentity::firstOrCreate(['provider' => 'google', 'provider_user_id' => $profile['sub']], ['global_user_id' => $user->id, 'provider_email' => strtolower($profile['email'])]);
            $user->update(['last_login_at' => now()]);
            return $user->fresh();
        });
    }

    private function normalize(string $name): string { return strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name))); }
}
