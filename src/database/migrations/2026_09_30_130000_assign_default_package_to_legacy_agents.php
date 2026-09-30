<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agents from before the SaaS model get the default package ("starter") as an active subscription.
     *
     * Only active and suspended Agents without any subscription row are touched, with the package's
     * current price and room limit as snapshot. When the package does not exist yet the migration does
     * nothing; run `php artisan subscriptions:assign-default` once it has been created.
     *
     * @return void
     */
    public function up(): void
    {
        $package = DB::table('packages')->where('code', (string) config('platform.billing.default_package', 'starter'))->first();
        if ($package === null) {
            return;
        }

        $now = now();
        DB::table('admins')
            ->whereIn('status', ['active', 'suspended'])
            ->whereNotExists(static fn ($query) => $query->select(DB::raw(1))->from('admin_subscriptions')->whereColumn('admin_subscriptions.admin_id', 'admins.id'))
            ->orderBy('id')
            ->pluck('id')
            ->each(static function (int|string $adminId) use ($package, $now): void {
                DB::table('admin_subscriptions')->insert([
                    'admin_id' => $adminId,
                    'package_id' => $package->id,
                    'status' => 'active',
                    'price_snapshot' => $package->monthly_price,
                    'proration_credit' => 0,
                    'room_limit_snapshot' => $package->room_limit,
                    'starts_at' => $now,
                    'expires_at' => $now->copy()->addMonthNoOverflow(),
                    'cancel_at_period_end' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Data migration: the subscriptions are kept on rollback (they may already be invoiced).
     * A Superadmin can cancel them from the Agent's subscription page.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
