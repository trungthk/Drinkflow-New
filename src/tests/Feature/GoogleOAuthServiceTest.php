<?php
namespace Tests\Feature;
use App\Models\GlobalUser;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
