@props([
    'compact' => false,
    'header' => false,
])

{{-- Reloads the current page (keeping its filters) and shows a spinner while it loads. See initAdminReloadButtons().
     "header" matches the action ribbon buttons (e.g. export) next to the page title. --}}
<button type="button" data-reload-page
    {{ $attributes->class([
        'inline-flex items-center gap-1.5 border border-outline-variant text-on-surface text-xs font-semibold transition-colors cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed',
        'bg-surface hover:bg-surface-container' => ! $header,
        'bg-surface-container hover:bg-surface-container-high shadow-xs' => $header,
        'h-9 px-3 rounded-lg' => ! $compact && ! $header,
        'px-3 py-1.5 rounded' => $compact,
        'px-3.5 py-2 rounded' => $header,
    ]) }}>
    <span class="material-symbols-outlined {{ $compact ? 'text-[14px]' : 'text-[16px]' }} {{ $header ? 'text-primary' : '' }}" data-reload-icon>refresh</span>
    <span>{{ __('admin.reload') }}</span>
</button>
