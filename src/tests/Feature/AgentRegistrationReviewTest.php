<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Enums\PermissionScope;
use App\Enums\SubscriptionStatus;
use App\Mail\AdminApprovedMail;
use App\Mail\AdminRejectedMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Package;
use App\Models\PermissionRecord;
use App\Models\Superadmin;
use App\Models\SuperadminAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * T14 + T15 + T16: pending registration queue, transactional approve/reject and decision emails.
 */
class AgentRegistrationReviewTest extends TestCase
{
    use RefreshDatabase;

    private Superadmin $owner;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->owner = $this->createSuperadmin(['name' => 'Owner', 'email' => 'owner@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $this->package = Package::create(['code' => 'starter', 'name' => 'Starter', 'monthly_price' => 199000, 'room_limit' => 3, 'status' => 'active']);
    }

    /**
     * Superadmin holding the given permissions.
     *
     * @param string $email Email.
     * @param array<string, PermissionScope> $grants Permission key => scope.
     * @return Superadmin Superadmin.
     */
    private function superadminWith(string $email, array $grants): Superadmin
    {
        $superadmin = Superadmin::create(['name' => 'Reviewer '.$email, 'email' => $email, 'password' => 'password123', 'status' => 'active']);
        foreach ($grants as $key => $scope) {
            $superadmin->permissions()->attach(PermissionRecord::query()->where('key', $key)->value('id'), ['scope' => $scope->value]);
        }

        return $superadmin;
    }

    private function applicant(string $email = 'applicant@example.test', bool $verified = true, ?Package $package = null): Admin
    {
        return Admin::create([
            'name' => 'Applicant '.$email,
            'company' => 'Tea Corp',
            'email' => $email,
            'password' => 'password123',
            'status' => AdminStatus::Pending->value,
            'requested_package_id' => ($package ?? $this->package)->id,
            'registered_at' => now(),
            'email_verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_queue_lists_only_pending_registrations(): void
    {
        $this->applicant('waiting@example.test');
        Admin::create(['name' => 'Active agent', 'email' => 'active@example.test', 'password' => 'password123', 'status' => 'active', 'registered_at' => now()]);

        $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.registrations.index'))
            ->assertOk()
            ->assertSee('waiting@example.test')
            ->assertDontSee('active@example.test');
        $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.registrations.index', ['verification' => 'unverified']))
            ->assertOk()
            ->assertDontSee('waiting@example.test');
    }

    public function test_queue_requires_the_approve_permission(): void
    {
        $viewer = $this->superadminWith('viewer@drinkflow.test', ['agent.view' => PermissionScope::All]);
        $applicant = $this->applicant();

        $this->actingAs($viewer, 'superadmin')->get(route('superadmin.registrations.index'))->assertForbidden();
        $this->actingAs($viewer, 'superadmin')->get(route('superadmin.registrations.show', $applicant))->assertForbidden();
        $this->actingAs($viewer, 'superadmin')->post(route('superadmin.registrations.approve', $applicant))->assertForbidden();
        $this->actingAs($viewer, 'superadmin')->post(route('superadmin.registrations.reject', $applicant), ['reason' => 'No'])->assertForbidden();
        $this->assertSame(AdminStatus::Pending, $applicant->fresh()->status);
    }

    public function test_review_page_renders(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.registrations.show', $applicant))
            ->assertOk()
            ->assertSee('Tea Corp')
            ->assertSee(route('superadmin.registrations.approve', $applicant), false);
    }

    public function test_approval_activates_the_agent_with_a_snapshot_subscription(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->owner, 'superadmin')
            ->post(route('superadmin.registrations.approve', $applicant))
            ->assertRedirect(route('superadmin.registrations.index'));

        $applicant->refresh();
        $this->assertSame(AdminStatus::Active, $applicant->status);
        $this->assertSame($this->owner->id, $applicant->reviewed_by_superadmin_id);
        $subscription = $applicant->activeSubscription()->firstOrFail();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame(199000, $subscription->price_snapshot);
        $this->assertSame(3, $subscription->room_limit_snapshot);
        $this->assertSame($this->owner->id, $subscription->approved_by_superadmin_id);
        $this->assertNotNull($subscription->expires_at);
        $this->assertTrue(AuditLog::query()->where('event', 'agent.approved')->where('target_id', $applicant->id)->exists());
        Mail::assertQueued(AdminApprovedMail::class, static fn (AdminApprovedMail $mail): bool => $mail->hasTo('applicant@example.test') && $mail->roomLimit === 3);
        // With the `all` scope and no manager chosen, nobody is assigned.
        $this->assertSame(0, SuperadminAdmin::query()->where('admin_id', $applicant->id)->count());
    }

    public function test_approved_agent_can_sign_in(): void
    {
        config(['captcha.disable' => true]);
        $applicant = $this->applicant();
        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant));

        $this->post(route('admin.login'), ['email' => 'applicant@example.test', 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticatedAs($applicant->fresh(), 'admin');
    }

    public function test_approval_can_override_the_package_and_assign_a_manager(): void
    {
        $pro = Package::create(['code' => 'pro', 'name' => 'Pro', 'monthly_price' => 499000, 'room_limit' => 10, 'status' => 'active']);
        $manager = $this->superadminWith('manager@drinkflow.test', ['agent.view' => PermissionScope::Managed]);
        $applicant = $this->applicant();

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant), [
            'package_id' => $pro->id,
            'manager_superadmin_id' => $manager->id,
        ])->assertRedirect();

        $this->assertSame(10, $applicant->activeSubscription()->firstOrFail()->room_limit_snapshot);
        $this->assertTrue(SuperadminAdmin::query()->where('admin_id', $applicant->id)->where('superadmin_id', $manager->id)->where('is_primary', true)->exists());
    }

    public function test_managed_approver_becomes_the_primary_manager(): void
    {
        $approver = $this->superadminWith('scoped@drinkflow.test', ['agent.approve' => PermissionScope::Managed, 'agent.view' => PermissionScope::Managed]);
        $applicant = $this->applicant();

        // The unassigned queue is visible to managed approvers too.
        $this->actingAs($approver, 'superadmin')->get(route('superadmin.registrations.index'))->assertOk()->assertSee('applicant@example.test');
        $this->actingAs($approver, 'superadmin')->post(route('superadmin.registrations.approve', $applicant), [
            'manager_superadmin_id' => $this->owner->id,
        ])->assertRedirect();

        $this->assertTrue(SuperadminAdmin::query()->where('admin_id', $applicant->id)->where('superadmin_id', $approver->id)->where('is_primary', true)->exists());
        $this->assertFalse(SuperadminAdmin::query()->where('admin_id', $applicant->id)->where('superadmin_id', $this->owner->id)->exists());
        $this->assertTrue(Admin::query()->visibleTo($approver->fresh())->whereKey($applicant->id)->exists());
    }

    public function test_reviewed_agent_is_hidden_from_managers_outside_their_scope(): void
    {
        $approver = $this->superadminWith('scoped@drinkflow.test', ['agent.approve' => PermissionScope::Managed, 'agent.view' => PermissionScope::Managed]);
        $applicant = $this->applicant();
        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant));

        $this->actingAs($approver, 'superadmin')->get(route('superadmin.registrations.show', $applicant))->assertForbidden();
    }

    public function test_unverified_registration_cannot_be_approved(): void
    {
        $applicant = $this->applicant(verified: false);

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant))->assertSessionHasErrors('registration');

        $this->assertSame(AdminStatus::Pending, $applicant->fresh()->status);
        $this->assertSame(0, $applicant->subscriptions()->count());
        Mail::assertNothingQueued();
    }

    public function test_unavailable_package_blocks_the_approval(): void
    {
        $applicant = $this->applicant();
        $this->package->update(['status' => 'archived']);

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant))->assertSessionHasErrors('package_id');

        $this->assertSame(AdminStatus::Pending, $applicant->fresh()->status);
        $this->assertSame(0, $applicant->subscriptions()->count());
    }

    public function test_a_registration_is_reviewed_only_once(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant));

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.approve', $applicant))->assertSessionHasErrors('registration');
        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.reject', $applicant), ['reason' => 'Late'])->assertSessionHasErrors('registration');

        $this->assertSame(1, $applicant->subscriptions()->count());
        $this->assertSame(AdminStatus::Active, $applicant->fresh()->status);
    }

    public function test_rejection_stores_the_reason_and_emails_it(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.reject', $applicant), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->owner, 'superadmin')
            ->post(route('superadmin.registrations.reject', $applicant), ['reason' => 'Company could not be verified'])
            ->assertRedirect(route('superadmin.registrations.index'));

        $applicant->refresh();
        $this->assertSame(AdminStatus::Rejected, $applicant->status);
        $this->assertSame('Company could not be verified', $applicant->rejection_reason);
        $this->assertSame(0, $applicant->subscriptions()->count());
        $this->assertTrue(AuditLog::query()->where('event', 'agent.rejected')->where('target_id', $applicant->id)->exists());
        Mail::assertQueued(AdminRejectedMail::class, static fn (AdminRejectedMail $mail): bool => $mail->reason === 'Company could not be verified');
    }

    public function test_rejected_agent_gets_an_explicit_login_error(): void
    {
        config(['captcha.disable' => true]);
        $applicant = $this->applicant();
        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.registrations.reject', $applicant), ['reason' => 'Duplicate account']);

        $this->post(route('admin.login'), ['email' => 'applicant@example.test', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => __('platform.registration.login_rejected')]);
    }

    public function test_decision_emails_render(): void
    {
        $applicant = $this->applicant();

        $this->assertStringContainsString('Starter', (new AdminApprovedMail($applicant, 'Starter', 3))->render());
        $this->assertStringContainsString('Duplicate', (new AdminRejectedMail($applicant, 'Duplicate'))->render());
    }
}
