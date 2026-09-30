<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Mail\AdminEmailVerificationMail;
use App\Models\Admin;
use App\Models\AdminSubscription;
use App\Models\Package;
use App\Models\Superadmin;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * T17 + T18: `admin_subscriptions` freezes the package terms at activation.
 */
class SubscriptionSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    private Admin $admin;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionService::class);
        $this->admin = Admin::create(['name' => 'Agent', 'email' => 'agent@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $this->package = Package::create(['code' => 'starter', 'name' => 'Starter', 'monthly_price' => 199000, 'room_limit' => 3, 'status' => 'active']);
    }

    public function test_schema_has_the_subscription_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('admin_subscriptions', [
            'admin_id', 'package_id', 'status', 'price_snapshot', 'room_limit_snapshot', 'starts_at', 'expires_at', 'approved_by_superadmin_id',
        ]));
    }

    public function test_activation_copies_the_package_terms(): void
    {
        $approver = Superadmin::create(['name' => 'Owner', 'email' => 'owner@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $this->travelTo(now()->startOfDay());

        $subscription = $this->service->activate($this->admin, $this->package, $approver);

        $this->assertSame(199000, $subscription->price_snapshot);
        $this->assertSame(3, $subscription->room_limit_snapshot);
        $this->assertSame($approver->id, $subscription->approved_by_superadmin_id);
        $this->assertTrue($subscription->starts_at->equalTo(now()));
        $this->assertTrue($subscription->expires_at->equalTo(now()->addMonthNoOverflow()));
    }

    public function test_editing_the_package_does_not_change_existing_subscriptions(): void
    {
        $subscription = $this->service->activate($this->admin, $this->package);

        $this->package->update(['monthly_price' => 999000, 'room_limit' => 50]);

        $subscription->refresh();
        $this->assertSame(199000, $subscription->price_snapshot);
        $this->assertSame(3, $subscription->room_limit_snapshot);
        // A new subscription takes the new terms.
        $this->assertSame(50, $this->service->activate($this->admin, $this->package->fresh())->room_limit_snapshot);
    }

    public function test_new_subscription_supersedes_the_current_one(): void
    {
        $first = $this->service->activate($this->admin, $this->package);
        $second = $this->service->activate($this->admin, $this->package);

        $this->assertSame(SubscriptionStatus::Superseded, $first->fresh()->status);
        $this->assertNotNull($first->fresh()->ended_at);
        $this->assertTrue($this->admin->activeSubscription()->firstOrFail()->is($second));
    }

    public function test_database_allows_only_one_active_subscription_per_agent(): void
    {
        $this->service->activate($this->admin, $this->package);

        $this->expectException(QueryException::class);
        AdminSubscription::create([
            'admin_id' => $this->admin->id,
            'package_id' => $this->package->id,
            'status' => SubscriptionStatus::Active->value,
            'price_snapshot' => 1,
            'room_limit_snapshot' => 1,
            'starts_at' => now(),
        ]);
    }

    public function test_package_with_subscriptions_cannot_be_removed_at_database_level(): void
    {
        $this->service->activate($this->admin, $this->package);

        $this->expectException(QueryException::class);
        $this->package->delete();
    }

    public function test_verification_email_renders(): void
    {
        $html = (new AdminEmailVerificationMail($this->admin, 'https://example.test/verify', 24))->render();

        $this->assertStringContainsString('https://example.test/verify', $html);
    }
}
