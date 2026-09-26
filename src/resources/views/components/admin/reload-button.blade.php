@props([
    'compact' => false,
    'header' => false,
])

{{-- Icon-only button that reloads the current page (keeping its filters); shows a spinner plus table/chart
     skeletons ([data-skeleton]) while it loads. See initAdminReloadButtons(). "header" matches the action ribbon buttons (e.g. export) next to the page title. --}}
<button type="button" data-reload-page data-tooltip="{{ __('admin.reload') }}" aria-label="{{ __('admin.reload') }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center shrink-0 border border-outline-variant text-on-surface text-xs font-semibold transition-colors cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed',
        'bg-surface hover:bg-surface-container' => ! $header,
        'bg-surface-container hover:bg-surface-container-high shadow-xs' => $header,
        'h-9 w-9 rounded-lg' => ! $compact && ! $header,
        'h-8.5 w-8.5 rounded' => $compact,
        'h-9.5 w-9.5 rounded' => $header,
    ]) }}>
    <span class="material-symbols-outlined {{ $compact ? 'text-[16px]' : 'text-[18px]' }} {{ $header ? 'text-primary' : '' }}" data-reload-icon aria-hidden="true">refresh</span>
</button>
