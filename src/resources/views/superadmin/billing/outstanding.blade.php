@extends('superadmin.layout', ['title' => __('platform.billing.outstanding_title'), 'active' => 'outstanding'])
@section('content')
    @php
        $money = static fn (int $amount): string => \App\Support\Helpers\FormatHelper::formatCurrency($amount);
    @endphp
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('platform.nav.platform_finance') }}</p>
            <h1>{{ __('platform.billing.outstanding_title') }}</h1>
            <p>{{ __('platform.billing.outstanding_description', ['amount' => $money($totalDue)]) }}</p>
        </div>
    </div>
    <x-superadmin.flash />
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('platform.billing.outstanding_title') }}</h2>
                <p>{{ __('platform.billing.invoices_count', ['count' => $invoices->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <x-superadmin.search-input :value="$filters['search']" placeholder="{{ __('platform.billing.search') }}" />
                <select name="status" class="sa-input" aria-label="{{ __('superadmin.common.status') }}">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (['open', 'overdue'] as $value)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ __('platform.billing.status.'.$value) }}</option>
                    @endforeach
                </select>
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('platform.billing.invoice') }}</th>
                        <th>{{ __('platform.subscriptions.agent') }}</th>
                        <th class="text-right">{{ __('platform.billing.total') }}</th>
                        <th class="text-right">{{ __('platform.billing.remaining') }}</th>
                        <th>{{ __('platform.billing.due_at') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><strong class="font-mono">{{ $invoice->number }}</strong><small class="block text-outline">{{ $invoice->package?->name }}</small></td>
                            <td><strong class="block truncate">{{ $invoice->admin?->name }}</strong><small class="block truncate text-outline">{{ $invoice->admin?->email }}</small></td>
                            <td class="text-right whitespace-nowrap">{{ $money($invoice->total) }}</td>
                            <td class="text-right whitespace-nowrap font-semibold">{{ $money($invoice->remaining()) }}</td>
                            <td class="whitespace-nowrap">{{ $invoice->due_at->toAppDate() }}</td>
                            <td>@include('superadmin.billing._invoice-status', ['invoice' => $invoice])</td>
                            <td class="text-right"><a class="sa-button secondary" href="{{ route('superadmin.billing.agent', $invoice->admin_id) }}"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.common.details') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-superadmin.empty-state icon="task_alt" :title="__('platform.billing.no_outstanding')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())
            <div class="mt-4">{{ $invoices->links() }}</div>
        @endif
    </section>
@endsection
