{{-- Search box with a clear (x) button; clicking it empties the field and submits the parent form (resources/js/superadmin/search-clear.js). --}}
@props([
    'name' => 'q',
    'value' => '',
])

<div class="sa-search" data-search-clear-wrap>
    <input type="search" name="{{ $name }}" value="{{ $value }}" autocomplete="off" {{ $attributes->merge(['class' => 'sa-input']) }}>
    <button type="button" class="sa-search-clear" data-search-clear aria-label="{{ __('superadmin.common.clear_search') }}" title="{{ __('superadmin.common.clear_search') }}" @if ((string) $value === '') hidden @endif>
        <span class="material-symbols-outlined text-[16px]">close</span>
    </button>
</div>
