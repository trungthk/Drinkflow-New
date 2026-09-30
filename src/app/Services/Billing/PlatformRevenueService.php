<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Models\AdminInvoice;
use App\Models\AdminPayment;
use App\Models\AdminSubscription;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Platform revenue figures, always restricted to the Agents a Superadmin may see for `revenue.view`.
 *
 * Every total (MRR, invoiced, collected, outstanding) goes through the Agent scope, so a `managed`
 * Superadmin never sees money of Agents outside their assignment, not even in aggregates.
 */
class PlatformRevenueService
{
    /** Months shown in the revenue trend. */
    public const TREND_MONTHS = 6;

    public function __construct(private readonly AgentScope $scope) {}

    /**
     * Headline figures.
     *
     * @param Superadmin $superadmin Viewer.
     * @return array{mrr: int, active_subscriptions: int, invoiced_month: int, collected_month: int, outstanding: int, overdue: int, overdue_count: int} Amounts in VND.
     */
    public function summary(Superadmin $superadmin): array
    {
        $monthStart = now()->startOfMonth();
        $active = $this->scoped(AdminSubscription::query()->active(), $superadmin, 'admin_subscriptions.admin_id');
        $outstanding = $this->scoped(AdminInvoice::query()->outstanding(), $superadmin, 'admin_invoices.admin_id');
        $overdue = $this->scoped(AdminInvoice::query()->where('status', InvoiceStatus::Overdue->value), $superadmin, 'admin_invoices.admin_id');

        return [
            'mrr' => (int) (clone $active)->sum('price_snapshot'),
            'active_subscriptions' => (clone $active)->count(),
            'invoiced_month' => (int) $this->scoped(AdminInvoice::query()->where('status', '!=', InvoiceStatus::Void->value)->where('issued_at', '>=', $monthStart), $superadmin, 'admin_invoices.admin_id')->sum('total'),
            'collected_month' => (int) $this->scoped(AdminPayment::query()->where('paid_at', '>=', $monthStart), $superadmin, 'admin_payments.admin_id')->sum('amount'),
            'outstanding' => (int) (clone $outstanding)->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')->value('due'),
            'overdue' => (int) (clone $overdue)->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')->value('due'),
            'overdue_count' => (clone $overdue)->count(),
        ];
    }

    /**
     * Invoiced and collected amounts of the last months, oldest first.
     *
     * @param Superadmin $superadmin Viewer.
     * @return array<int, array{month: string, invoiced: int, collected: int}> One row per month (Y-m).
     */
    public function trend(Superadmin $superadmin): array
    {
        $rows = [];
        for ($offset = self::TREND_MONTHS - 1; $offset >= 0; $offset--) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($offset);
            $end = $start->copy()->addMonthNoOverflow();
            $rows[] = [
                'month' => $start->format('Y-m'),
                'invoiced' => (int) $this->scoped(AdminInvoice::query()->where('status', '!=', InvoiceStatus::Void->value)->where('issued_at', '>=', $start)->where('issued_at', '<', $end), $superadmin, 'admin_invoices.admin_id')->sum('total'),
                'collected' => (int) $this->scoped(AdminPayment::query()->where('paid_at', '>=', $start)->where('paid_at', '<', $end), $superadmin, 'admin_payments.admin_id')->sum('amount'),
            ];
        }

        return $rows;
    }

    /**
     * Active subscriptions and MRR per package.
     *
     * @param Superadmin $superadmin Viewer.
     * @return array<int, array{package: string, subscriptions: int, mrr: int}> Rows by MRR, highest first.
     */
    public function byPackage(Superadmin $superadmin): array
    {
        return $this->scoped(AdminSubscription::query()->active(), $superadmin, 'admin_subscriptions.admin_id')
            ->with('package:id,name')
            ->get(['id', 'package_id', 'price_snapshot'])
            ->groupBy('package_id')
            ->map(static fn ($group): array => [
                'package' => (string) ($group->first()->package?->name ?? '—'),
                'subscriptions' => $group->count(),
                'mrr' => (int) $group->sum('price_snapshot'),
            ])
            ->sortByDesc('mrr')
            ->values()
            ->all();
    }

    /**
     * Restrict a query to Agents visible for `revenue.view`.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query Query.
     * @param Superadmin $superadmin Viewer.
     * @param string $adminColumn Agent column.
     * @return Builder<TModel> Constrained query.
     */
    private function scoped(Builder $query, Superadmin $superadmin, string $adminColumn): Builder
    {
        return $this->scope->apply($query, $superadmin, Permission::RevenueView, $adminColumn);
    }
}
