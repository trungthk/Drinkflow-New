<?php
namespace Tests\Feature;
use App\Models\GlobalUser;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class GoogleOAuthServiceTest extends TestCase {
    use RefreshDatabase;
    public function test_company_domain_and_verified_email_are_required(): void {
        config(['services.google.allowed_domains' => ['company.com']]);
        $service = app(GoogleOAuthService::class);
        $this->expectException(ValidationException::class);
        $service->validateProfile(['sub' => '1', 'email' => 'a@gmail.com', 'email_verified' => true]);
    }
    public function test_same_google_identity_does_not_duplicate_global_user(): void {
        config(['services.google.allowed_domains' => ['company.com']]);
        $service = app(GoogleOAuthService::class);
        $profile = ['sub' => 'same-sub', 'email' => 'A@company.com', 'name' => 'Nguyễn A', 'email_verified' => true];
        $first = $service->resolveUser($profile);
        $second = $service->resolveUser($profile);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('global_users', 1);
        $this->assertDatabaseCount('oauth_identities', 1);
    }

    public function test_google_callback_redirects_new_user_to_profile_onboarding(): void {
        config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'client-secret', 'services.google.redirect' => 'http://localhost/auth/google/callback', 'services.google.allowed_domains' => ['company.com']]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response(['sub' => 'new-sub', 'email' => 'new@company.com', 'name' => 'New User', 'email_verified' => true]),
        ]);

        $response = $this->withSession(['google_oauth_state' => 'state-value'])
            ->get('/auth/google/callback?code=auth-code&state=state-value');

        $response->assertRedirect(route('user.profile.page'));
        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('global_users', ['email' => 'new@company.com']);
    }
}
