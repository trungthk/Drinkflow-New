@php
    /** @var \Illuminate\Support\Collection<int, string> $toppings */
    $toppings = collect($toppings ?? [])->filter()->values();
@endphp
@if ($toppings->isNotEmpty() || ($icePercent ?? null) !== null || ($sugarPercent ?? null) !== null)
    <div class="flex flex-wrap items-center gap-1 pt-0.5">
        @foreach ($toppings as $toppingLabel)
            <span class="inline-block px-1.5 py-0.5 rounded bg-surface-container text-[10px] text-outline border border-outline-variant/50">+ {{ $toppingLabel }}</span>
        @endforeach
        @if (($icePercent ?? null) !== null)
            <span class="inline-block px-1.5 py-0.5 rounded bg-sky-50 text-[10px] text-sky-700 border border-sky-200/70">{{ $icePercent }}% {{ __('room.orders.ice') }}</span>
        @endif
        @if (($sugarPercent ?? null) !== null)
            <span class="inline-block px-1.5 py-0.5 rounded bg-amber-50 text-[10px] text-amber-700 border border-amber-200/70">{{ $sugarPercent }}% {{ __('room.orders.sugar') }}</span>
        @endif
    </div>
@endif
