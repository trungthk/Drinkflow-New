{{-- "Rooms X / Limit" with the quota state (RoomQuotaService::usage()). --}}
@props(['usage'])
@php
    $percent = $usage['limit'] > 0 ? min(100, (int) round($usage['used'] / $usage['limit'] * 100)) : 100;
    $tone = match ($usage['state']) {
        'full', 'none' => ['bar' => 'bg-error', 'text' => 'text-error', 'chip' => 'bg-error-container text-on-error-container'],
        'near' => ['bar' => 'bg-amber-500', 'text' => 'text-amber-700', 'chip' => 'bg-amber-50 text-amber-800'],
        default => ['bar' => 'bg-primary', 'text' => 'text-primary', 'chip' => 'bg-emerald-50 text-emerald-800'],
    };
@endphp
<div {{ $attributes->merge(['class' => 'rounded-xl border border-outline-variant bg-surface-container-lowest p-4']) }} data-room-quota="{{ $usage['state'] }}">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-outline">{{ __('platform.rooms.quota_title') }}</p>
            <p class="text-xl font-bold {{ $tone['text'] }}">{{ __('platform.rooms.quota_usage', ['used' => $usage['used'], 'limit' => $usage['limit']]) }}</p>
        </div>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $tone['chip'] }}">{{ __('platform.rooms.quota_state.'.$usage['state']) }}</span>
    </div>
    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-surface-container" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $usage['limit'] }}" aria-valuenow="{{ $usage['used'] }}" aria-label="{{ __('platform.rooms.quota_title') }}">
        <div class="h-full {{ $tone['bar'] }}" style="width: {{ $percent }}%"></div>
    </div>
    <p class="mt-2 text-xs text-on-surface-variant">
        {{ $usage['has_subscription'] ? __('platform.rooms.quota_hint', ['remaining' => $usage['remaining']]) : __('platform.rooms.no_subscription') }}
    </p>
</div>
