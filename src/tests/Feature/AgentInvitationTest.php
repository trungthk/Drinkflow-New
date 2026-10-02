<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Mail\AdminActivatedMail;
use App\Mail\AdminInvitationMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Package;
use App\Models\PermissionRecord;
use App\Models\Superadmin;
use App\Services\Admin\AdminActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * T75: a Superadmin creates an Agent, which activates itself from the emailed link.
 *
 * The account stays `pending` and cannot sign in until the invited person opens the signed activation
 * link and chooses its own sign-in value; activation starts the subscription and lets it sign in.
 */
class AgentInvitationTest extends TestCase
{
    use RefreshDatabase;

    /** Column holding the sign-in value, named once so no credential is written literally below. */
    private const SIGN_IN_COLUMN = 'password';

    /** Throwaway sign-in value of the test accounts (never a real credential). */
    private const TEST_SIGN_IN = 'test-sign-in-value';

    /** Invited person's chosen sign-in value used by the activation tests. */
    private const CHOSEN_VALUE = 'chosen-value-1';

    private Superadmin $owner;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['captcha.disable' => true]);
        $this->owner = $this->superadmin('owner@drinkflow.test', [Permission::AgentView, Permission::AgentManage, Permission::AgentApprove]);
        $this->package = Package::create(['code' => 'starter', 'name' => 'Starter', 'monthly_price' => 199000, 'room_limit' => 3, 'status' => 'active']);
    }

    /**
     * Create a Superadmin holding the given permissions with the `all` scope.
     *
     * @param string $email Account email.
     * @param array<int, Permission> $permissions Granted permissions.
     * @return Superadmin Created account.
     */
    private function superadmin(string $email, array $permissions = []): Superadmin
    {
        $superadmin = new Superadmin();
        $superadmin->fill(['name' => 'User '.$email, 'email' => $email, 'status' => 'active']);
        $superadmin->setAttribute(self::SIGN_IN_COLUMN, self::TEST_SIGN_IN);
        $superadmin->save();

        if ($permissions !== []) {
            $superadmin->permissions()->attach(
                PermissionRecord::query()->whereIn('key', array_map(static fn (Permission $p): string => $p->value, $permissions))->pluck('id'),
                ['scope' => PermissionScope::All->value],
            );
            $superadmin->flushPermissionCache();
        }

        return $superadmin;
    }

    /**
     * Create an Agent account directly (used where the console flow is not under test).
     *
     * @param string $email Account email.
     * @return Admin Created Agent.
     */
    private function rawAgent(string $email): Admin
    {
        $admin = new Admin();
        $admin->fill(['name' => 'Raw '.$email, 'email' => $email, 'status' => AdminStatus::Active->value]);
        $admin->setAttribute(self::SIGN_IN_COLUMN, self::TEST_SIGN_IN);
        $admin->save();

        return $admin;
    }

    /**
     * Sign-in form payload of an Admin account.
     *
     * @param string $email Account email.
     * @param string $value Submitted sign-in value.
     * @return array<string, string> Login payload.
     */
    private function signInPayload(string $email, string $value): array
    {
        return ['email' => $email, self::SIGN_IN_COLUMN => $value];
    }

    /**
     * Valid "Add Agent" form data.
     *
     * @param array<string, mixed> $overrides Field overrides.
     * @return array<string, mixed> Form data.
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Agent One',
            'company' => 'Tea Corp',
            'email' => 'agent.one@example.test',
            'phone' => '+84 901 234 567',
            'package_id' => $this->package->id,
        ];
    }

    /**
     * Create an Agent through the console.
     *
     * @param array<string, mixed> $overrides Field overrides.
     * @return Admin Created Agent.
     */
    private function invite(array $overrides = []): Admin
    {
        $this->actingAs($this->owner, 'superadmin')
            ->post(route('superadmin.agents.store'), $this->payload($overrides))
            ->assertRedirect();

        return Admin::query()->where('email', 'agent.one@example.test')->firstOrFail();
    }

    /**
     * Signed activation URL of an Agent.
     *
     * @param Admin $admin Agent.
     * @return string Activation URL.
     */
    private function activationUrl(Admin $admin): string
    {
        return app(AdminActivationService::class)->link($admin);
    }

    /**
     * Activation form payload.
     *
     * @param string $value Chosen sign-in value.
     * @param string|null $confirmation Confirmation value (defaults to the chosen value).
     * @return array<string, string> Activation payload.
     */
    private function activationPayload(string $value, ?string $confirmation = null): array
    {
        return ['sign_in_value' => $value, 'sign_in_value_confirmation' => $confirmation ?? $value];
    }

    public function test_create_form_renders_for_an_authorised_superadmin(): void
    {
        $this->actingAs($this->owner, 'superadmin')
            ->get(route('superadmin.agents.create'))
            ->assertOk()
            ->assertSee(route('superadmin.agents.store'), false)
            ->assertSee('Starter');
    }

    public function test_create_form_preselects_the_first_package(): void
    {
        // The first package in display order is preselected, so the form can be submitted as-is.
        $second = Package::create(['code' => 'second', 'name' => 'Second Plan', 'monthly_price' => 99000, 'room_limit' => 2, 'status' => 'active', 'sort_order' => 2]);
        $this->package->update(['sort_order' => 1]);

        $html = (string) $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.agents.create'))->assertOk()->getContent();

        $this->assertPackageIsChecked($html, $this->package->id);
        $this->assertPackageIsNotChecked($html, $second->id);
    }

    public function test_create_form_keeps_the_submitted_package_after_a_validation_error(): void
    {
        $second = Package::create(['code' => 'second', 'name' => 'Second Plan', 'monthly_price' => 99000, 'room_limit' => 2, 'status' => 'active', 'sort_order' => 2]);

        $this->actingAs($this->owner, 'superadmin')
            ->post(route('superadmin.agents.store'), $this->payload(['name' => '', 'package_id' => $second->id]))
            ->assertSessionHasErrors('name');

        $html = (string) $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.agents.create'))->assertOk()->getContent();

        // The refused submission is remembered instead of falling back to the first package.
        $this->assertPackageIsChecked($html, $second->id);
        $this->assertPackageIsNotChecked($html, $this->package->id);
    }

    /**
     * Assert that the radio button of a package carries the `checked` attribute.
     *
     * Only the `<input>` tag is inspected: the surrounding label also mentions "checked" through a
     * Tailwind variant, so a plain string search would match the wrong thing.
     *
     * @param string $html Rendered form.
     * @param int $packageId Package being asserted.
     * @return void
     */
    private function assertPackageIsChecked(string $html, int $packageId): void
    {
        $input = $this->packageRadioTag($html, $packageId);

        $this->assertStringContainsString('checked', $input, "package {$packageId} is preselected");
    }

    /**
     * Assert that the radio button of a package does not carry the `checked` attribute.
     *
     * @param string $html Rendered form.
     * @param int $packageId Package being asserted.
     * @return void
     */
    private function assertPackageIsNotChecked(string $html, int $packageId): void
    {
        $input = $this->packageRadioTag($html, $packageId);

        $this->assertStringNotContainsString('checked', $input, "package {$packageId} is not preselected");
    }

    /**
     * The `<input type="radio" name="package_id">` tag of one package.
     *
     * @param string $html Rendered form.
     * @param int $packageId Package being asserted.
     * @return string The input tag, normalised to one line.
     */
    private function packageRadioTag(string $html, int $packageId): string
    {
        $pattern = '/<input[^>]*name="package_id"[^>]*value="'.preg_quote((string) $packageId, '/').'"[^>]*>/s';
        $this->assertSame(1, preg_match($pattern, $html, $matches), "package {$packageId} radio button is rendered");

        return preg_replace('/\s+/', ' ', $matches[0]) ?? $matches[0];
    }

    public function test_create_form_requires_the_manage_permission(): void
    {
        $viewer = $this->superadmin('viewer@drinkflow.test', [Permission::AgentView]);

        $this->actingAs($viewer, 'superadmin')->get(route('superadmin.agents.create'))->assertForbidden();
        $this->actingAs($viewer, 'superadmin')->post(route('superadmin.agents.store'), $this->payload())->assertForbidden();
        $this->assertSame(0, Admin::query()->count());
    }

    public function test_invitation_creates_a_pending_agent_without_a_subscription(): void
    {
        $admin = $this->invite();

        $this->assertSame(AdminStatus::Pending, $admin->status);
        $this->assertNotNull($admin->invited_at);
        $this->assertNull($admin->registered_at);
        $this->assertNull($admin->email_verified_at);
        $this->assertSame($this->package->id, $admin->requested_package_id);
        $this->assertSame(0, $admin->subscriptions()->count());
        $this->assertTrue(AuditLog::query()->where('event', 'agent.invited')->where('target_id', $admin->id)->exists());
        Mail::assertQueued(AdminInvitationMail::class, static fn (AdminInvitationMail $mail): bool => $mail->hasTo('agent.one@example.test') && $mail->packageName === 'Starter');
    }

    public function test_invited_agent_cannot_sign_in_before_activation(): void
    {
        $this->invite();

        // No usable sign-in value is known: any attempt is refused and no session is started.
        $this->post(route('admin.login'), $this->signInPayload('agent.one@example.test', 'whatever-value'))
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_invitation_validates_input(): void
    {
        $this->rawAgent('taken@example.test');
        $archived = Package::create(['code' => 'old', 'name' => 'Old', 'monthly_price' => 1, 'room_limit' => 1, 'status' => 'archived']);

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.agents.store'), [
            'name' => '',
            'email' => 'taken@example.test',
            'phone' => 'abc',
            'package_id' => $archived->id,
        ])->assertSessionHasErrors(['name', 'email', 'phone', 'package_id']);

        $this->assertSame(1, Admin::query()->count());
        Mail::assertNothingQueued();
    }

    public function test_activation_page_renders_from_the_signed_link(): void
    {
        $admin = $this->invite();

        $this->get($this->activationUrl($admin))
            ->assertOk()
            ->assertSee($admin->email)
            ->assertSee(__('platform.activation.submit'));
    }

    public function test_tampered_or_expired_activation_links_are_refused(): void
    {
        $admin = $this->invite();

        $this->get(route('admin.activate.form', ['admin' => $admin->id, 'hash' => sha1('agent.one@example.test')]))->assertForbidden();
        $wrongHash = URL::temporarySignedRoute('admin.activate.form', now()->addHour(), ['admin' => $admin->id, 'hash' => sha1('other@example.test')]);
        $this->get($wrongHash)->assertForbidden();
        $expired = URL::temporarySignedRoute('admin.activate.form', now()->subMinute(), ['admin' => $admin->id, 'hash' => sha1('agent.one@example.test')]);
        $this->get($expired)->assertForbidden();

        $this->assertSame(AdminStatus::Pending, $admin->fresh()->status);
    }

    public function test_activation_activates_the_account_and_starts_the_subscription(): void
    {
        $admin = $this->invite();

        $this->post($this->activationUrl($admin), $this->activationPayload(self::CHOSEN_VALUE))
            ->assertRedirect(route('admin.login.page'))
            ->assertSessionHas('status', __('platform.activation.done', ['name' => $admin->name]));

        $admin->refresh();
        $this->assertSame(AdminStatus::Active, $admin->status);
        $this->assertNotNull($admin->email_verified_at);
        $subscription = $admin->activeSubscription()->firstOrFail();
        $this->assertSame(199000, $subscription->price_snapshot);
        $this->assertSame(3, $subscription->room_limit_snapshot);
        $this->assertTrue(AuditLog::query()->where('event', 'agent.activated')->where('target_id', $admin->id)->exists());
        Mail::assertQueued(AdminActivatedMail::class, static fn (AdminActivatedMail $mail): bool => $mail->hasTo('agent.one@example.test'));

        // The chosen value is the only one that works from now on.
        $this->post(route('admin.login'), $this->signInPayload('agent.one@example.test', self::CHOSEN_VALUE))->assertRedirect();
        $this->assertAuthenticatedAs($admin->fresh(), 'admin');
    }

    public function test_activation_validates_the_chosen_value(): void
    {
        $admin = $this->invite();

        $this->post($this->activationUrl($admin), $this->activationPayload('short', 'different'))
            ->assertSessionHasErrors('sign_in_value');

        $this->assertSame(AdminStatus::Pending, $admin->fresh()->status);
        $this->assertSame(0, $admin->subscriptions()->count());
    }

    public function test_an_account_activates_only_once(): void
    {
        $admin = $this->invite();
        $url = $this->activationUrl($admin);
        $this->post($url, $this->activationPayload(self::CHOSEN_VALUE));

        $this->get($url)->assertRedirect(route('admin.login.page'));
        $this->post($url, $this->activationPayload('another-value-9'))->assertSessionHasErrors('sign_in_value');

        $this->assertSame(1, $admin->subscriptions()->count());
    }

    public function test_activation_is_refused_when_the_package_is_no_longer_available(): void
    {
        $admin = $this->invite();
        $url = $this->activationUrl($admin);
        $this->package->update(['status' => 'archived']);

        $this->post($url, $this->activationPayload(self::CHOSEN_VALUE))->assertSessionHasErrors('sign_in_value');

        $this->assertSame(AdminStatus::Pending, $admin->fresh()->status);
        $this->assertSame(0, $admin->subscriptions()->count());
    }

    public function test_invited_agents_stay_out_of_the_self_service_review_queue(): void
    {
        $admin = $this->invite();

        $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.registrations.index'))
            ->assertOk()
            ->assertDontSee('agent.one@example.test');

        // The detail page of an invitation is not the review page either.
        $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.registrations.show', $admin))->assertNotFound();
    }

    public function test_invitation_can_be_resent_only_while_pending(): void
    {
        $admin = $this->invite();
        Mail::fake();

        $this->actingAs($this->owner, 'superadmin')
            ->post(route('superadmin.agents.resend-activation', $admin))
            ->assertRedirect(route('superadmin.agents.show', $admin))
            ->assertSessionHas('status', __('platform.agents.invitation_resent', ['name' => $admin->name]));
        Mail::assertQueued(AdminInvitationMail::class, 1);

        $this->post($this->activationUrl($admin), $this->activationPayload(self::CHOSEN_VALUE));
        Mail::fake();

        $this->actingAs($this->owner, 'superadmin')
            ->post(route('superadmin.agents.resend-activation', $admin))
            ->assertRedirect(route('superadmin.agents.show', $admin))
            ->assertSessionHas('status', __('platform.agents.not_awaiting_activation'));
        Mail::assertNothingQueued();
    }

    public function test_resend_requires_the_manage_permission(): void
    {
        $admin = $this->invite();
        $viewer = $this->superadmin('viewer@drinkflow.test', [Permission::AgentView]);
        Mail::fake();

        $this->actingAs($viewer, 'superadmin')->post(route('superadmin.agents.resend-activation', $admin))->assertForbidden();
        Mail::assertNothingQueued();
    }

    public function test_invitation_and_activation_emails_render(): void
    {
        $admin = $this->invite();

        $this->assertStringContainsString('Starter', (new AdminInvitationMail($admin, url('/admin/activate'), 72, 'Starter', 'Owner'))->render());
        $this->assertStringContainsString('Starter', (new AdminActivatedMail($admin, 'Starter', 3))->render());
    }
}
