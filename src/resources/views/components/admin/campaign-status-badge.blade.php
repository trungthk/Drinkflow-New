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
    $dotClass = $statusEnum?->dotClass($expired) ?? 'bg-slate-400';
    $label = $statusEnum?->label($expired) ?? __('admin.status_closed');
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-mono text-[11px] font-semibold border shadow-2xs {$badgeClass}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotClass }}"></span>
    <span>{{ $label }}</span>
</span>
