@props([
    'id' => null,
    'name' => null,
    'value' => '',
    'placeholder' => __('admin.search_placeholder'),
    'debounce' => '300',
    'class' => '',
    'containerClass' => 'relative flex-1 max-w-sm',
])

<div class="{{ $containerClass }}">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </span>
    <input
        type="text"
        @if($id) id="{{ $id }}" @endif
        @if($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        data-search-input="true"
        data-debounce="{{ $debounce }}"
        {{ $attributes->merge(['class' => 'w-full pl-9 pr-8 py-2 text-sm bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 text-slate-800 dark:text-slate-200 placeholder-slate-400 transition-colors duration-200 ' . $class]) }}
    />
    <button
        type="button"
        class="search-clear-btn hidden absolute inset-y-0 right-0 items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
        title="{{ __('admin.clear_search') }}"
        aria-label="{{ __('admin.clear_search') }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
