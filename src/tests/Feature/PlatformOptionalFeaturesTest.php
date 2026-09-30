<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Enums\PlatformPaymentMethod;
use App\Models\Admin;
use App\Models\AdminInvoice;
use App\Models\Package;
use App\Models\Superadmin;
use App\Models\SuperadminRole;
use App\Services\Billing\Gateway\SignedLinkGateway;
use App\Services\Billing\PlatformBillingService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T71–T74: superadmin roles, grace period, automatic suspension and online payment.
 */
class PlatformOptionalFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Superadmin $owner;

    private Admin $agent;

    private PlatformBillingService $billing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->createSuperadmin(['name' => 'Owner', 'email' => 'owner@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $this->agent = Admin::create(['name' => 'Agent', 'email' => 'agent@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $package = Package::create(['code' => 'std', 'name' => 'Standard', 'monthly_price' => 300000, 'room_limit' => 3, 'status' => 'active']);
        app(SubscriptionService::class)->activate($this->agent, $package);
        $this->billing = app(PlatformBillingService::class);
        $this->billing->generateInvoices();
        config([
            'platform.billing.due_days' => 7,
            'platform.billing.grace_days' => 5,
            'platform.billing.auto_suspend' => true,
            'platform.payments.enabled' => true,
            'platform.payments.checkout_url' => 'https://pay.example.test/checkout',
            'platform.payments.webhook_secret' => 'test-webhook-secret',
        ]);
    }

    private function invoice(): AdminInvoice
    {
        return $this->agent->invoices()->firstOrFail();
    }

    // ── T71: roles ────────────────────────────────────────────────────────────────────────

    public function test_role_is_created_and_applied_to_a_superadmin(): void
    {
        $support = Superadmin::create(['name' => 'Support', 'email' => 'support@drinkflow.test', 'password' => 'password123', 'status' => 'active']);

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.roles.store'), [
            'code' => 'finance', 'name' => 'Finance', 'permissions' => ['revenue.view', 'debt.view', 'package.view'],
            'scopes' => ['revenue.view' => 'managed', 'debt.view' => 'all'],
        ])->assertRedirect(route('superadmin.roles.index'));
        $role = SuperadminRole::query()->where('code', 'finance')->firstOrFail();
        $this->assertSame(['debt.view' => 'all', 'package.view' => 'all', 'revenue.view' => 'managed'], $role->grants());

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.superadmins.role', $support), ['role_id' => $role->id])->assertRedirect();

        $support = $support->fresh();
        $this->assertTrue($support->hasPermission(Permission::DebtView));
        $this->assertFalse($support->hasPermission(Permission::AgentView));
        $this->assertSame($role->id, $support->superadmin_role_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'superadmin_role.applied', 'target_id' => $support->id]);
        $this->actingAs($this->owner, 'superadmin')->get(route('superadmin.roles.edit', $role))->assertOk()->assertSee('finance');
    }

    public function test_applying_a_role_keeps_the_last_manager_guard(): void
    {
        $viewer = SuperadminRole::create(['code' => 'viewer', 'name' => 'Viewer']);

        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.superadmins.role', $this->owner), ['role_id' => $viewer->id])->assertSessionHasErrors('superadmin');
        $this->assertTrue($this->owner->fresh()->hasPermission(Permission::SuperadminManage));
    }

    public function test_roles_require_superadmin_permissions(): void
    {
        $nobody = Superadmin::create(['name' => 'Nobody', 'email' => 'nobody@drinkflow.test', 'password' => 'password123', 'status' => 'active']);

        $this->actingAs($nobody, 'superadmin')->get(route('superadmin.roles.index'))->assertForbidden();
        $this->actingAs($nobody, 'superadmin')->post(route('superadmin.roles.store'), ['code' => 'x', 'name' => 'X'])->assertForbidden();
    }

    // ── T72 + T73: grace period and automatic suspension ─────────────────────────────────

    public function test_agent_keeps_access_during_the_grace_period_then_is_suspended(): void
    {
        $this->travel(9)->days();
        $this->billing->processOverdue();
        $this->assertSame(InvoiceStatus::Overdue, $this->invoice()->status);

        $this->artisan('billing:enforce-overdue')->expectsOutputToContain('Agents suspended: 0')->assertSuccessful();
        $this->assertSame(AdminStatus::Active, $this->agent->fresh()->status);
        $this->actingAs($this->agent, 'admin')->get(route('admin.billing.index'))->assertOk()->assertSee('data-billing-warning', false);

        $this->travel(4)->days();
        $this->artisan('billing:enforce-overdue')->expectsOutputToContain('Agents suspended: 1')->assertSuccessful();
        $agent = $this->agent->fresh();
        $this->assertSame(AdminStatus::Suspended, $agent->status);
        $this->assertNotNull($agent->billing_suspended_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'agent.auto_suspended', 'target_id' => $agent->id]);
    }

    public function test_paying_the_overdue_invoice_lifts_the_automatic_suspension(): void
    {
        $this->travel(20)->days();
        $this->billing->processOverdue();
        app(\App\Services\Billing\BillingEnforcementService::class)->suspendOverdueAgents();
        $this->assertSame(AdminStatus::Suspended, $this->agent->fresh()->status);

        $this->billing->recordPayment($this->invoice(), $this->owner, 300000, PlatformPaymentMethod::BankTransfer);

        $agent = $this->agent->fresh();
        $this->assertSame(AdminStatus::Active, $agent->status);
        $this->assertNull($agent->billing_suspended_at);
    }

    public function test_manual_suspension_is_not_lifted_by_payment(): void
    {
        $this->actingAs($this->owner, 'superadmin')->post(route('superadmin.agents.suspend', $this->agent), ['reason' => 'Abuse']);

        $this->billing->recordPayment($this->invoice(), $this->owner, 300000, PlatformPaymentMethod::Cash);

        $this->assertSame(AdminStatus::Suspended, $this->agent->fresh()->status);
    }

    public function test_automatic_suspension_is_off_unless_enabled(): void
    {
        config(['platform.billing.auto_suspend' => false]);
        $this->travel(30)->days();
        $this->billing->processOverdue();

        $this->artisan('billing:enforce-overdue')->expectsOutputToContain('Agents suspended: 0');
        $this->assertSame(AdminStatus::Active, $this->agent->fresh()->status);
    }

    // ── T74: online payment ───────────────────────────────────────────────────────────────

    public function test_agent_is_sent_to_a_signed_checkout_for_its_own_invoice(): void
    {
        $invoice = $this->invoice();
        $other = Admin::create(['name' => 'Other', 'email' => 'other@drinkflow.test', 'password' => 'password123', 'status' => 'active']);

        $response = $this->actingAs($this->agent, 'admin')->get(route('admin.billing.pay', $invoice));
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://pay.example.test/checkout?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame($invoice->number, $query['invoice']);
        $this->assertSame(hash_hmac('sha256', $invoice->number.'|300000', 'test-webhook-secret'), $query['signature']);

        $this->flushSession();
        $this->actingAs($other, 'admin')->get(route('admin.billing.pay', $invoice))->assertNotFound();
    }

    public function test_signed_webhook_records_the_payment_once(): void
    {
        $invoice = $this->invoice();
        $body = json_encode(['transaction_id' => 'TX-1', 'invoice_number' => $invoice->number, 'amount' => 300000, 'status' => 'paid']);
        $signature = app(SignedLinkGateway::class)->sign($body);
        $send = fn (string $payload, string $sig) => $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $sig], $payload);

        $send($body, $signature)->assertOk()->assertJsonPath('status', 'recorded');
        $send($body, $signature)->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(PlatformPaymentMethod::Online, $invoice->payments()->first()->method);
        $this->assertNull($invoice->payments()->first()->recorded_by_superadmin_id);
    }

    public function test_webhook_rejects_bad_signatures_and_ignores_unknown_invoices(): void
    {
        $body = json_encode(['transaction_id' => 'TX-2', 'invoice_number' => $this->invoice()->number, 'amount' => 300000, 'status' => 'paid']);

        $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'forged'], $body)->assertForbidden();
        $this->assertSame(0, $this->invoice()->payments()->count());

        $unknown = json_encode(['transaction_id' => 'TX-3', 'invoice_number' => 'INV-000000-999999', 'amount' => 10, 'status' => 'paid']);
        $this->call('POST', route('payments.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => app(SignedLinkGateway::class)->sign($unknown)], $unknown)
            ->assertOk()->assertJsonPath('status', 'ignored');
    }

    public function test_online_payment_is_hidden_when_disabled(): void
    {
        config(['platform.payments.enabled' => false]);

        $this->actingAs($this->agent, 'admin')->get(route('admin.billing.index'))->assertOk()->assertDontSee(__('platform.billing.pay_online'));
        $this->actingAs($this->agent, 'admin')->get(route('admin.billing.pay', $this->invoice()))->assertNotFound();
    }
}
