<?php
namespace Tests\Feature;
use App\Models\GlobalUser;
use App\Models\AdminAccount;
use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
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
        $initialCount = GlobalUser::count();
        $profile = ['sub' => 'same-sub', 'email' => 'A@company.com', 'name' => 'Nguyễn A', 'email_verified' => true];
        $first = $service->resolveUser($profile);
        $second = $service->resolveUser($profile);
        $this->assertSame($first->id, $second->id);
        $this->assertSame($initialCount + 1, GlobalUser::count());
        $this->assertDatabaseHas('global_users', ['email' => 'a@company.com']);
        $this->assertDatabaseHas('oauth_identities', ['provider_user_id' => 'same-sub']);
    }

    public function test_google_callback_redirects_new_user_to_profile_onboarding(): void {
        config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'client-secret', 'services.google.redirect' => 'http://localhost/auth/google/callback', 'services.google.allowed_domains' => ['company.com']]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response(['sub' => 'new-sub', 'email' => 'new@company.com', 'name' => 'New User', 'email_verified' => true]),
        ]);

        $response = $this->withSession(['google_oauth_state' => 'state-value'])
            ->get('/auth/google/callback?code=auth-code&state=state-value');

        $response->assertRedirect(route('user.me.dashboard'));
        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('global_users', ['email' => 'new@company.com']);
    }

    public function test_google_com_domain_throws_validation_exception(): void {
        $service = app(GoogleOAuthService::class);
        $this->expectException(ValidationException::class);
        $service->validateProfile(['sub' => '1', 'email' => 'test@google.com', 'email_verified' => true]);
    }

    public function test_login_with_google_com_domain_is_rejected_and_redirects_to_login_source_with_error(): void {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.allowed_domains' => ['company.com'],
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-sub',
                'email' => 'employee@google.com',
                'name' => 'Google Employee',
                'email_verified' => true,
            ]),
        ]);

        $response = $this->withSession([
            'google_oauth_state' => 'state-value',
            'google_oauth_login_source' => 'http://localhost:8080/',
        ])->get('/auth/google/callback?code=auth-code&state=state-value');

        $response->assertRedirect('http://localhost:8080/');
        $response->assertSessionHas('login_error');
        $this->assertFalse(auth('web')->check());
    }

    /**
     * Ensure a failed Workspace identity check keeps the Admin two-factor challenge active.
     *
     * @return void
     */
    public function test_failed_admin_workspace_login_keeps_two_factor_challenge_visible(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.allowed_domains' => ['company.com'],
        ]);

        $admin = AdminAccount::create([
            'name' => 'Two Factor Admin',
            'email' => 'admin@company.com',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => AdminRole::Admin,
            'status' => AdminStatus::Active,
            'two_factor_enabled' => true,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'different-admin-sub',
                'email' => 'different@company.com',
                'name' => 'Different Admin',
                'email_verified' => true,
            ]),
        ]);

        $response = $this->withSession([
            'google_oauth_state' => 'state-value',
            'google_oauth_login_source' => url('/admin/login'),
            'admin_google_2fa_admin_id' => $admin->id,
            'admin_google_2fa_remember' => false,
        ])->get('/auth/google/callback?code=auth-code&state=state-value');

        $response->assertRedirect(url('/admin/login'))
            ->assertSessionHasErrors('email')
            ->assertSessionHas('admin_google_2fa_admin_id', $admin->id);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(__('admin.sign_in_google_workspace'))
            ->assertDontSee('id="admin-email"', false)
            ->assertDontSee('id="admin-password"', false)
            ->assertDontSee('name="remember"', false);
    }

    /**
     * Ensure a matching Workspace identity completes Admin two-factor authentication.
     *
     * @return void
     */
    public function test_matching_admin_workspace_login_completes_two_factor_authentication(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.allowed_domains' => ['company.com'],
        ]);

        $admin = AdminAccount::create([
            'name' => 'Two Factor Admin',
            'email' => 'admin@company.com',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => AdminRole::Admin,
            'status' => AdminStatus::Active,
            'two_factor_enabled' => true,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'matching-admin-sub',
                'email' => $admin->email,
                'name' => $admin->name,
                'email_verified' => true,
            ]),
        ]);

        $response = $this->withSession([
            'google_oauth_state' => 'state-value',
            'google_oauth_login_source' => url('/admin/login'),
            'admin_google_2fa_admin_id' => $admin->id,
            'admin_google_2fa_remember' => true,
        ])->get('/auth/google/callback?code=auth-code&state=state-value');

        $response->assertRedirect(route('admin.landing'))
            ->assertSessionMissing('admin_google_2fa_admin_id')
            ->assertSessionMissing('admin_google_2fa_remember');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertDatabaseHas('audit_logs', [
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'event' => 'admin.logged_in',
        ]);
    }
}
