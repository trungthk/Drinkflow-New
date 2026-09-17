@props([
    'icon' => 'inbox',
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-10 px-4 text-center rounded-xl border border-dashed border-outline-variant/80 bg-surface-container-low/30']) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high/60 text-outline mb-2.5">
        <span class="material-symbols-outlined text-[26px]">{{ $icon }}</span>
    </div>
    @if($title)
        <h4 class="text-xs font-bold text-on-surface mb-0.5">{{ $title }}</h4>
    @endif
    <p class="text-xs text-outline font-medium max-w-sm leading-relaxed">
        {{ $description ?? $slot }}
    </p>
</div>
