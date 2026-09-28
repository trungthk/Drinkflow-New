{{-- Tag list input (resources/js/admin/tags-input.js): Enter / comma / space / paste adds entries, Backspace or × removes.
     Values are read by the page script from [data-tags-input] (see readTagsInput()); the server validates them again. --}}
@props([
    'id',
    'name',
    'values' => [],
    'label' => '',
    'hint' => '',
    'placeholder' => '',
    // "domain" or "ip": client-side format check before a tag is added.
    'validate' => 'domain',
    // "allow" (emerald chips) or "block" (red chips).
    'variant' => 'allow',
    'icon' => 'sell',
    'max' => \App\Services\Room\RoomAccessPolicy::MAX_ENTRIES,
])

@php
    $chipClass = $variant === 'block'
        ? 'bg-rose-50 text-rose-700 border-rose-200'
        : 'bg-emerald-50 text-emerald-800 border-emerald-200';
@endphp

<div class="space-y-1.5" data-tags-input="{{ $name }}" data-validate="{{ $validate }}" data-max="{{ (int) $max }}"
    data-chip-class="{{ $chipClass }}"
    data-i18n="{{ json_encode([
        'invalid' => $validate === 'ip' ? __('admin.room_access_invalid_ip', ['value' => ':value']) : __('admin.room_access_invalid_domain', ['value' => ':value']),
        'duplicate' => __('admin.room_access_duplicate', ['value' => ':value']),
        'tooMany' => __('admin.room_access_too_many', ['max' => (int) $max]),
        'remove' => __('admin.tags_remove', ['value' => ':value']),
    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">
    <label for="{{ $id }}" class="flex items-center gap-1.5 font-semibold text-xs text-on-surface">
        <span class="material-symbols-outlined text-[16px] {{ $variant === 'block' ? 'text-rose-600' : 'text-primary' }}">{{ $icon }}</span>
        <span>{{ $label }}</span>
        <span class="ml-auto font-mono text-[10px] font-normal text-outline" data-tags-count>{{ count($values) }}/{{ (int) $max }}</span>
    </label>
    <div class="flex min-h-10 w-full flex-wrap items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-2 py-1.5 transition-colors focus-within:border-primary focus-within:ring-1 focus-within:ring-primary cursor-text"
        data-tags-box>
        @foreach ($values as $value)
            <span class="inline-flex max-w-full items-center gap-1 rounded-md border px-2 py-0.5 font-mono text-[11px] font-semibold {{ $chipClass }}" data-tag="{{ $value }}">
                <span class="truncate">{{ $value }}</span>
                <button type="button" class="-mr-0.5 inline-flex h-4 w-4 items-center justify-center rounded opacity-70 hover:opacity-100 cursor-pointer" data-tag-remove
                    aria-label="{{ __('admin.tags_remove', ['value' => $value]) }}">
                    <span class="material-symbols-outlined text-[14px]">close</span>
                </button>
            </span>
        @endforeach
        <input id="{{ $id }}" type="text" autocomplete="off" spellcheck="false" placeholder="{{ $placeholder }}"
            class="min-w-[10rem] flex-1 border-0 bg-transparent p-0.5 text-xs text-on-surface outline-hidden focus:ring-0 placeholder:text-outline"
            data-tags-field>
    </div>
    <p class="hidden text-[11px] font-medium text-error" data-tags-error role="alert"></p>
    @if ($hint)
        <p class="text-[11px] leading-relaxed text-outline">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>
