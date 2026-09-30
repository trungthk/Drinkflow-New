@extends('superadmin.layout', ['title' => $agent->name, 'active' => 'outstanding'])
@section('content')
    @php
        $money = static fn (int $amount): string => \App\Support\Helpers\FormatHelper::formatCurrency($amount);
        $fieldInput = 'sa-input !min-w-0 w-full !py-1.5 text-xs';
    @endphp
    <div class="superadmin-heading">
        <div>
            <a class="sa-back-link" href="{{ route('superadmin.billing.outstanding') }}">← {{ __('platform.billing.outstanding_title') }}</a>
            <h1>{{ $agent->name }}</h1>
            <p>{{ $agent->email }} · {{ __('platform.billing.balance', ['amount' => $money($balance)]) }}</p>
        </div>
    </div>
    <x-superadmin.flash />

    <section class="sa-card sa-section">
        <div class="sa-section-header"><div><h2>{{ __('platform.billing.invoices') }}</h2></div></div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('platform.billing.invoice') }}</th>
                        <th>{{ __('platform.subscriptions.period') }}</th>
                        <th class="text-right">{{ __('platform.billing.total') }}</th>
                        <th class="text-right">{{ __('platform.billing.paid') }}</th>
                        <th>{{ __('platform.billing.due_at') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        @if ($canManage)
                            <th>{{ __('superadmin.common.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>
                                <strong class="font-mono">{{ $invoice->number }}</strong>
                                <small class="block text-outline">{{ $invoice->package?->name }}@if ($invoice->credit > 0) · {{ __('platform.billing.credit', ['amount' => $money($invoice->credit)]) }}@endif</small>
                            </td>
                            <td class="whitespace-nowrap">{{ $invoice->period_start->toAppDate() }} → {{ $invoice->period_end->toAppDate() }}</td>
                            <td class="text-right whitespace-nowrap">{{ $money($invoice->total) }}</td>
                            <td class="text-right whitespace-nowrap">{{ $money($invoice->paid_amount) }}</td>
                            <td class="whitespace-nowrap">{{ $invoice->due_at->toAppDate() }}</td>
                            <td>@include('superadmin.billing._invoice-status', ['invoice' => $invoice])</td>
                            @if ($canManage)
                                <td class="min-w-[280px]">
                                    @if ($invoice->status->isOutstanding())
                                        <form method="POST" action="{{ route('superadmin.billing.payments.store', $invoice) }}" class="grid grid-cols-2 gap-1.5"
                                            data-confirm="{{ __('platform.billing.payment_confirm', ['number' => $invoice->number]) }}">
                                            @csrf
                                            <input name="amount" type="number" min="1" max="{{ $invoice->remaining() }}" value="{{ $invoice->remaining() }}" required class="{{ $fieldInput }}" aria-label="{{ __('platform.billing.amount') }}">
                                            <select name="method" class="{{ $fieldInput }}" aria-label="{{ __('platform.billing.method') }}">
                                                @foreach ($methods as $method)
                                                    <option value="{{ $method->value }}">{{ __('platform.billing.methods.'.$method->value) }}</option>
                                                @endforeach
                                            </select>
                                            <input name="reference" type="text" maxlength="120" placeholder="{{ __('platform.billing.reference') }}" class="{{ $fieldInput }} col-span-2">
                                            <button type="submit" class="sa-button col-span-2"><span class="material-symbols-outlined text-[16px]">price_check</span>{{ __('platform.billing.mark_paid') }}</button>
                                        </form>
                                        @if ($invoice->paid_amount === 0)
                                            <form method="POST" action="{{ route('superadmin.billing.invoices.void', $invoice) }}" class="mt-1.5 flex gap-1.5"
                                                data-confirm="{{ __('platform.billing.void_confirm', ['number' => $invoice->number]) }}">
                                                @csrf
                                                <input name="reason" type="text" required minlength="3" maxlength="1000" placeholder="{{ __('platform.billing.void_reason') }}" class="{{ $fieldInput }}">
                                                <button type="submit" class="sa-button danger">{{ __('platform.billing.void') }}</button>
                                            </form>
                                        @endif
                                    @elseif ($invoice->void_reason)
                                        <small class="text-outline">{{ $invoice->void_reason }}</small>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 7 : 6 }}"><x-superadmin.empty-state icon="receipt_long" :title="__('platform.billing.no_invoices')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="sa-card sa-section mt-4">
        <div class="sa-section-header"><div><h2>{{ __('platform.billing.payments') }}</h2></div></div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr><th>{{ __('platform.billing.paid_at') }}</th><th>{{ __('platform.billing.invoice') }}</th><th class="text-right">{{ __('platform.billing.amount') }}</th><th>{{ __('platform.billing.method') }}</th><th>{{ __('platform.billing.reference') }}</th><th>{{ __('platform.billing.recorded_by') }}</th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap">{{ $payment->paid_at->toAppDateTime() }}</td>
                            <td class="font-mono">{{ $payment->invoice?->number }}</td>
                            <td class="text-right whitespace-nowrap">{{ $money($payment->amount) }}</td>
                            <td>{{ __('platform.billing.methods.'.$payment->method->value) }}</td>
                            <td>{{ $payment->reference ?? '—' }}</td>
                            <td>{{ $payment->recordedBy?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-superadmin.empty-state icon="payments" :title="__('platform.billing.no_payments')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
