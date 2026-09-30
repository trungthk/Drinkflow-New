{{-- Overdue platform invoice notice for the signed-in Agent: grace period end and automatic suspension. --}}
@php
    $billingAdmin = auth('admin')->user();
    $enforcement = app(\App\Services\Billing\BillingEnforcementService::class);
    $overdueInvoice = $billingAdmin ? $enforcement->oldestOverdue($billingAdmin) : null;
@endphp
@if ($overdueInvoice)
    <div class="rounded-lg border border-error/30 bg-error-container px-4 py-3 text-sm text-on-error-container" role="alert" data-billing-warning>
        <strong>{{ __('platform.billing.overdue_warning', ['number' => $overdueInvoice->number, 'amount' => \App\Support\Helpers\FormatHelper::formatCurrency($overdueInvoice->remaining())]) }}</strong>
        <span class="block mt-0.5">
            {{ config('platform.billing.auto_suspend')
                ? __('platform.billing.grace_suspend_notice', ['date' => $enforcement->graceEndsAt($overdueInvoice)->toAppDate()])
                : __('platform.billing.grace_notice', ['date' => $enforcement->graceEndsAt($overdueInvoice)->toAppDate()]) }}
        </span>
    </div>
@endif
