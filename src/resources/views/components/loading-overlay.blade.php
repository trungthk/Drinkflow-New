{{-- Content-loading overlay: spinner + label covering its closest `relative` parent.
     Toggle it from JS with setLoadingOverlay() (resources/js/shared/loading-overlay.js).
     Used by the close-campaign summary and the admin/room dashboard charts. --}}
@props([
    'label' => null,
    'visible' => false,
])

<div {{ $attributes->class([
        'absolute inset-0 z-10 items-center justify-center gap-2 rounded-xl bg-surface-container-low/85 text-xs text-outline font-medium',
        'flex' => $visible,
        'hidden' => ! $visible,
    ]) }}
    data-loading-overlay
    role="status"
    aria-live="polite">
    <svg class="animate-spin h-4 w-4 text-primary" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
    <span>{{ $label ?? __('global.common.loading') }}</span>
</div>
