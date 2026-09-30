<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Models\Admin;
use App\Models\AdminSubscription;
use App\Models\Package;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Agents from before the SaaS model are put on the default ("starter") package.
 */
class DefaultPackageAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Package $starter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->starter = Package::create(['code' => 'starter', 'name' => 'Starter', 'monthly_price' => 0, 'room_limit' => 1, 'status' => 'active']);
    }

    private function admin(string $email, AdminStatus $status = AdminStatus::Active): Admin
    {
        return Admin::create(['name' => $email, 'email' => $email, 'password' => 'password123', 'status' => $status->value]);
    }

    public function test_migration_puts_legacy_agents_on_the_starter_package(): void
    {
        $legacy = $this->admin('legacy@drinkflow.test');
        $suspended = $this->admin('suspended@drinkflow.test', AdminStatus::Suspended);
        $pending = $this->admin('pending@drinkflow.test', AdminStatus::Pending);
        $subscribed = $this->admin('subscribed@drinkflow.test');
        $pro = Package::create(['code' => 'pro', 'name' => 'Pro', 'monthly_price' => 1, 'room_limit' => 9, 'status' => 'active']);
        app(SubscriptionService::class)->activate($subscribed, $pro);

        (require database_path('migrations/2026_09_30_130000_assign_default_package_to_legacy_agents.php'))->up();

        foreach ([$legacy, $suspended] as $admin) {
            $subscription = $admin->activeSubscription()->firstOrFail();
            $this->assertSame($this->starter->id, $subscription->package_id);
            $this->assertSame(1, $subscription->room_limit_snapshot);
            $this->assertSame(0, $subscription->price_snapshot);
            $this->assertNotNull($subscription->expires_at);
        }
        $this->assertSame(0, $pending->subscriptions()->count());
        $this->assertSame(9, $subscribed->activeSubscription()->firstOrFail()->room_limit_snapshot);
        $this->assertSame(1, $subscribed->subscriptions()->count());
    }

    public function test_command_is_idempotent_and_audited(): void
    {
        $legacy = $this->admin('legacy@drinkflow.test');

        $this->artisan('subscriptions:assign-default')->expectsOutputToContain('Agents given the default package: 1')->assertSuccessful();
        $this->artisan('subscriptions:assign-default')->expectsOutputToContain('Agents given the default package: 0')->assertSuccessful();

        $this->assertSame(1, $legacy->subscriptions()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'subscription.started', 'target_id' => $legacy->id]);
    }

    public function test_cancelled_agent_is_not_resubscribed(): void
    {
        $agent = $this->admin('agent@drinkflow.test');
        app(SubscriptionService::class)->activate($agent, $this->starter);
        app(SubscriptionService::class)->cancelNow($agent);

        $this->artisan('subscriptions:assign-default')->expectsOutputToContain(': 0');
        $this->assertNull($agent->activeSubscription()->first());
    }

    public function test_nothing_happens_without_the_default_package(): void
    {
        $this->admin('legacy@drinkflow.test');
        config(['platform.billing.default_package' => 'missing']);

        $this->artisan('subscriptions:assign-default')->expectsOutputToContain(': 0');
        $this->assertSame(0, AdminSubscription::query()->count());
    }

    public function test_legacy_agent_gets_the_starter_quota(): void
    {
        $legacy = $this->admin('legacy@drinkflow.test');
        $this->artisan('subscriptions:assign-default');

        $this->actingAs($legacy, 'admin')->post(route('admin.rooms.store'), ['name' => 'First', 'timezone' => 'UTC', 'language' => 'vi'])->assertSessionHasNoErrors();
        $this->actingAs($legacy, 'admin')->post(route('admin.rooms.store'), ['name' => 'Second', 'timezone' => 'UTC', 'language' => 'vi'])->assertSessionHasErrors('room');
    }
}
