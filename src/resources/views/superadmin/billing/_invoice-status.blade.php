{{-- Status pill of a platform invoice. --}}
@php
    $tone = ['open' => 'pending', 'overdue' => 'blocked', 'paid' => 'active', 'void' => 'inactive'][$invoice->status->value] ?? 'inactive';
@endphp
<span class="status-pill status-{{ $tone }}"><span class="status-dot"></span>{{ __('platform.billing.status.'.$invoice->status->value) }}</span>
