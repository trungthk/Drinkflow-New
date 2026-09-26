@props([
    'campaign' => null,
    'status' => null,
    'isExpired' => null,
])

@php
    $statusEnum = null;
    if ($campaign) {
        $statusEnum = $campaign->status instanceof \App\Enums\CampaignStatus
            ? $campaign->status
            : \App\Enums\CampaignStatus::tryFrom((string) $campaign->status);
        $expired = $isExpired ?? ($statusEnum === \App\Enums\CampaignStatus::Active && $campaign->deadline && $campaign->deadline->isPast());
    } elseif ($status) {
        $statusEnum = $status instanceof \App\Enums\CampaignStatus
            ? $status
            : \App\Enums\CampaignStatus::tryFrom((string) $status);
        $expired = $isExpired ?? false;
    } else {
        $statusEnum = \App\Enums\CampaignStatus::Closed;
        $expired = false;
    }

    $badgeClass = $statusEnum?->badgeClass($expired) ?? 'bg-surface text-outline border-outline-variant';
    $icon = $statusEnum?->icon($expired) ?? 'task_alt';
    $label = $statusEnum?->label($expired) ?? __('admin.status_closed');
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 pl-1.5 pr-2.5 py-0.5 rounded-full text-[11px] font-semibold border whitespace-nowrap {$badgeClass}"]) }}>
    <span class="material-symbols-outlined text-[14px] leading-none shrink-0" aria-hidden="true">{{ $icon }}</span>
    <span>{{ $label }}</span>
</span>
