<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Mail\AdminEmailVerificationMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Package;
use App\Services\Admin\AdminEmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * T11 + T12 + T13: public Agent registration, email verification and package request.
 */
class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        config(['captcha.disable' => true]);
        Mail::fake();
        $this->package = Package::create(['code' => 'starter', 'name' => 'Starter', 'monthly_price' => 199000, 'room_limit' => 3, 'status' => 'active']);
    }

    /**
     * Valid registration form data.
     *
     * @param array<string, mixed> $overrides Field overrides.
     * @return array<string, mixed> Form data.
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Nguyen Van A',
            'company' => 'Tea Corp',
            'email' => 'Agent@Example.test',
            'phone' => '+84 901 234 567',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'package_id' => $this->package->id,
        ];
    }

    private function register(array $overrides = []): Admin
    {
        $this->post(route('admin.register'), $this->payload($overrides))->assertRedirect(route('admin.register.pending'));

        return Admin::query()->where('email', mb_strtolower((string) ($overrides['email'] ?? 'agent@example.test')))->firstOrFail();
    }

    public function test_form_lists_only_active_packages(): void
    {
        Package::create(['code' => 'hidden', 'name' => 'Hidden plan', 'monthly_price' => 1, 'room_limit' => 1, 'status' => 'inactive']);

        $this->get(route('admin.register.page'))
            ->assertOk()
            ->assertSee('Starter')
            ->assertDontSee('Hidden plan');
    }

    public function test_registration_creates_a_pending_agent_with_requested_package_only(): void
    {
        $admin = $this->register();

        $this->assertSame(AdminStatus::Pending, $admin->status);
        $this->assertSame('Tea Corp', $admin->company);
        $this->assertSame('+84 901 234 567', $admin->phone);
        $this->assertSame($this->package->id, $admin->requested_package_id);
        $this->assertNotNull($admin->registered_at);
        $this->assertNull($admin->email_verified_at);
        // T13: the package is only requested; no subscription or quota before approval.
        $this->assertSame(0, $admin->subscriptions()->count());
        $this->assertTrue(AuditLog::query()->where('event', 'admin.registered')->where('target_id', $admin->id)->exists());
        Mail::assertQueued(AdminEmailVerificationMail::class, static fn (AdminEmailVerificationMail $mail): bool => $mail->hasTo('agent@example.test'));
    }

    public function test_registration_validates_input(): void
    {
        $inactive = Package::create(['code' => 'old', 'name' => 'Old', 'monthly_price' => 1, 'room_limit' => 1, 'status' => 'archived']);
        Admin::create(['name' => 'Taken', 'email' => 'taken@example.test', 'password' => 'password123', 'status' => 'active']);

        $this->post(route('admin.register'), $this->payload([
            'email' => 'taken@example.test',
            'phone' => 'abc',
            'password_confirmation' => 'different',
            'package_id' => $inactive->id,
            'name' => '',
        ]))->assertSessionHasErrors(['email', 'phone', 'password', 'package_id', 'name']);

        $this->assertSame(1, Admin::query()->count());
        Mail::assertNothingQueued();
    }

    public function test_captcha_is_required_when_enabled(): void
    {
        config(['captcha.disable' => false]);

        $this->post(route('admin.register'), $this->payload())->assertSessionHasErrors('captcha');
        $this->assertSame(0, Admin::query()->count());
    }

    public function test_pending_agent_cannot_sign_in(): void
    {
        $this->register();

        $this->post(route('admin.login'), ['email' => 'agent@example.test', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors(['email' => __('platform.registration.login_pending_verification')]);
        $this->assertGuest('admin');

        Admin::query()->where('email', 'agent@example.test')->update(['email_verified_at' => now()]);
        $this->post(route('admin.login'), ['email' => 'agent@example.test', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors(['email' => __('platform.registration.login_pending_review')]);
        $this->assertGuest('admin');
    }

    public function test_wrong_password_on_pending_agent_gets_the_generic_error(): void
    {
        $this->register();

        $this->post(route('admin.login'), ['email' => 'agent@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => __('admin.invalid_credentials')]);
    }

    public function test_signed_link_verifies_the_email(): void
    {
        $admin = $this->register();
        $link = app(AdminEmailVerificationService::class)->link($admin);

        $this->get($link)->assertRedirect(route('admin.login.page'))->assertSessionHas('status', __('platform.registration.email_verified'));

        $admin->refresh();
        $this->assertNotNull($admin->email_verified_at);
        $this->assertSame(AdminStatus::Pending, $admin->status);
        $this->assertTrue(AuditLog::query()->where('event', 'admin.email_verified')->exists());
    }

    public function test_tampered_or_expired_links_are_refused(): void
    {
        $admin = $this->register();

        $this->get(route('admin.register.verify', ['admin' => $admin->id, 'hash' => sha1('agent@example.test')]))->assertForbidden();
        $wrongHash = URL::temporarySignedRoute('admin.register.verify', now()->addHour(), ['admin' => $admin->id, 'hash' => sha1('other@example.test')]);
        $this->get($wrongHash)->assertForbidden();
        $expired = URL::temporarySignedRoute('admin.register.verify', now()->subMinute(), ['admin' => $admin->id, 'hash' => sha1('agent@example.test')]);
        $this->get($expired)->assertForbidden();

        $this->assertNull($admin->fresh()->email_verified_at);
    }

    public function test_resend_only_mails_pending_unverified_registrations(): void
    {
        $admin = $this->register();
        Mail::fake();

        $this->post(route('admin.register.resend'), ['email' => 'unknown@example.test'])->assertRedirect(route('admin.register.pending'));
        Mail::assertNothingQueued();

        $this->post(route('admin.register.resend'), ['email' => 'AGENT@example.test'])
            ->assertRedirect(route('admin.register.pending'))
            ->assertSessionHas('status', __('platform.registration.verification_resent'));
        Mail::assertQueued(AdminEmailVerificationMail::class, 1);

        $admin->update(['email_verified_at' => now()]);
        Mail::fake();
        $this->post(route('admin.register.resend'), ['email' => 'agent@example.test']);
        Mail::assertNothingQueued();
    }

    public function test_pending_page_renders(): void
    {
        $this->withSession(['registered_email' => 'agent@example.test'])
            ->get(route('admin.register.pending'))
            ->assertOk()
            ->assertSee('agent@example.test');
    }

    public function test_login_page_links_to_registration(): void
    {
        $this->get(route('admin.login.page'))->assertOk()->assertSee(route('admin.register.page'), false);
        $this->get(route('superadmin.login.page'))->assertOk()->assertDontSee(route('admin.register.page'), false);
    }
}
