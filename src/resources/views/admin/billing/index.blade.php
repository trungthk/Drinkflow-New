@php
    $money = static fn (int $amount): string => \App\Support\Helpers\FormatHelper::formatCurrency($amount);
    $tones = ['open' => 'bg-amber-50 text-amber-800', 'overdue' => 'bg-error-container text-on-error-container', 'paid' => 'bg-emerald-50 text-emerald-800', 'void' => 'bg-surface-container text-on-surface-variant'];
@endphp
<x-admin.layout :title="__('platform.billing.history_title')" active="billing" :breadcrumb="__('platform.billing.history_title')">
    <div class="max-w-7xl mx-auto space-y-6">
        <section class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">{{ __('platform.billing.history_title') }}</h1>
                <p class="mt-1.5 max-w-3xl text-sm text-on-surface-variant">{{ __('platform.billing.history_subtitle') }}</p>
            </div>
            <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-5 py-3" data-billing-balance="{{ $balance }}">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-outline">{{ __('platform.billing.amount_due') }}</p>
                <p class="text-xl font-bold {{ $balance > 0 ? 'text-error' : 'text-primary' }}">{{ $money($balance) }}</p>
            </div>
        </section>

        <x-admin.billing-warning />

        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant"><h2 class="text-sm font-bold text-on-surface">{{ __('platform.billing.invoices') }}</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider text-outline">
                        <tr>
                            <th class="px-5 py-3 text-left">{{ __('platform.billing.invoice') }}</th>
                            <th class="px-5 py-3 text-left">{{ __('platform.subscriptions.period') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('platform.billing.total') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('platform.billing.paid') }}</th>
                            <th class="px-5 py-3 text-left">{{ __('platform.billing.due_at') }}</th>
                            <th class="px-5 py-3 text-left">{{ __('validation.attributes.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="px-5 py-3"><strong class="font-mono">{{ $invoice->number }}</strong><small class="block text-outline">{{ $invoice->package?->name }}@if ($invoice->credit > 0) · {{ __('platform.billing.credit', ['amount' => $money($invoice->credit)]) }}@endif</small></td>
                                <td class="px-5 py-3 whitespace-nowrap">{{ $invoice->period_start->toAppDate() }} → {{ $invoice->period_end->toAppDate() }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">{{ $money($invoice->total) }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">{{ $money($invoice->paid_amount) }}</td>
                                <td class="px-5 py-3 whitespace-nowrap">{{ $invoice->due_at->toAppDate() }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tones[$invoice->status->value] ?? '' }}">{{ __('platform.billing.status.'.$invoice->status->value) }}</span>
                                    @if ($onlinePayment && $invoice->status->isOutstanding() && $invoice->remaining() > 0)
                                        <a href="{{ route('admin.billing.pay', $invoice) }}" class="ml-2 text-xs font-semibold text-primary hover:underline">{{ __('platform.billing.pay_online') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-outline">{{ __('platform.billing.no_invoices') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($invoices->hasPages())
                <div class="px-5 py-3">{{ $invoices->links() }}</div>
            @endif
        </section>

        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant"><h2 class="text-sm font-bold text-on-surface">{{ __('platform.billing.payments') }}</h2></div>
            <ul class="divide-y divide-outline-variant/60 text-sm">
                @forelse ($payments as $payment)
                    <li class="px-5 py-3 flex flex-wrap justify-between gap-2">
                        <span>{{ $payment->paid_at->toAppDateTime() }} · <span class="font-mono">{{ $payment->invoice?->number }}</span> · {{ __('platform.billing.methods.'.$payment->method->value) }}</span>
                        <strong>{{ $money($payment->amount) }}</strong>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-outline">{{ __('platform.billing.no_payments') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-admin.layout>
