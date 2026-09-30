@extends('superadmin.layout', ['title' => __('platform.billing.revenue_title'), 'active' => 'revenue'])
@section('content')
    @php
        $money = static fn (int $amount): string => \App\Support\Helpers\FormatHelper::formatCurrency($amount);
        $peak = max(1, collect($trend)->flatMap(static fn (array $row): array => [$row['invoiced'], $row['collected']])->max());
        $kpis = [
            ['mrr', 'autorenew', $money($summary['mrr']), __('platform.billing.active_subscriptions', ['count' => $summary['active_subscriptions']])],
            ['invoiced_month', 'receipt_long', $money($summary['invoiced_month']), null],
            ['collected_month', 'savings', $money($summary['collected_month']), null],
            ['outstanding', 'account_balance_wallet', $money($summary['outstanding']), null],
            ['overdue', 'warning', $money($summary['overdue']), __('platform.billing.invoices_count', ['count' => $summary['overdue_count']])],
        ];
    @endphp
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('platform.nav.platform_finance') }}</p>
            <h1>{{ __('platform.billing.revenue_title') }}</h1>
            <p>{{ __('platform.billing.revenue_description') }}</p>
        </div>
    </div>
    <section class="sa-grid kpis" aria-label="{{ __('platform.billing.revenue_title') }}">
        @foreach ($kpis as [$key, $icon, $value, $hint])
            <article class="sa-card sa-kpi" data-kpi="{{ $key }}">
                <span class="material-symbols-outlined text-primary">{{ $icon }}</span>
                <p class="text-xs text-outline">{{ __('platform.billing.kpi.'.$key) }}</p>
                <strong class="text-lg">{{ $value }}</strong>
                @if ($hint)
                    <small class="block text-outline">{{ $hint }}</small>
                @endif
            </article>
        @endforeach
    </section>

    <div class="sa-split mt-4">
        <section class="sa-card sa-section">
            <div class="sa-section-header"><div><h2>{{ __('platform.billing.trend') }}</h2><p>{{ __('platform.billing.trend_hint') }}</p></div></div>
            <div class="space-y-3">
                @foreach ($trend as $row)
                    <div class="grid grid-cols-[64px_1fr] items-center gap-3 text-xs">
                        <span class="font-mono text-outline">{{ $row['month'] }}</span>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2"><span class="h-2 rounded bg-primary/40" style="width: {{ max(1, round($row['invoiced'] / $peak * 100)) }}%"></span><span class="whitespace-nowrap">{{ __('platform.billing.invoiced') }}: {{ $money($row['invoiced']) }}</span></div>
                            <div class="flex items-center gap-2"><span class="h-2 rounded bg-primary" style="width: {{ max(1, round($row['collected'] / $peak * 100)) }}%"></span><span class="whitespace-nowrap">{{ __('platform.billing.collected') }}: {{ $money($row['collected']) }}</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header"><div><h2>{{ __('platform.billing.by_package') }}</h2></div></div>
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead><tr><th>{{ __('platform.packages.package') }}</th><th class="text-right">{{ __('platform.billing.subscriptions') }}</th><th class="text-right">{{ __('platform.billing.kpi.mrr') }}</th></tr></thead>
                    <tbody>
                        @forelse ($byPackage as $row)
                            <tr><td>{{ $row['package'] }}</td><td class="text-right">{{ $row['subscriptions'] }}</td><td class="text-right whitespace-nowrap">{{ $money($row['mrr']) }}</td></tr>
                        @empty
                            <tr><td colspan="3"><x-superadmin.empty-state icon="payments" :title="__('platform.billing.no_revenue')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
