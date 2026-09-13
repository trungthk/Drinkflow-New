@props([
    'name' => 'q',
    'value' => '',
    'placeholder' => '',
    'id' => null,
    'debounce' => 400,
    'inputClass' => '',
])

@php
    $placeholder = $placeholder ?: __('global.search.placeholder');
    $inputId = $id ?: 'search-input-' . \Illuminate\Support\Str::random(6);
    $defaultClass = 'w-full pl-9 pr-9 h-[38px] bg-white border border-slate-200 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#006948] focus:ring-1 focus:ring-[#006948] transition-all outline-none';
    $finalClass = $inputClass ?: $defaultClass;
@endphp

<div
    x-data="{
        query: '{{ addslashes($value) }}',
        timer: null,
        submitForm() {
            if (this.$refs.searchInput) {
                const form = this.$refs.searchInput.closest('form');
                if (form) form.submit();
            }
        },
        onInput() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.submitForm();
            }, {{ $debounce }});
        },
        clearSearch() {
            this.query = '';
            if (this.$refs.searchInput) {
                this.$refs.searchInput.value = '';
            }
            this.submitForm();
        }
    }"
    class="relative flex-1"
>
    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
    <input
        x-ref="searchInput"
        x-model="query"
        @input="onInput()"
        @keydown.enter.prevent="clearTimeout(timer); submitForm()"
        type="text"
        name="{{ $name }}"
        id="{{ $inputId }}"
        placeholder="{{ $placeholder }}"
        class="{{ $finalClass }}"
        {{ $attributes->except(['class', 'name', 'value', 'placeholder', 'id']) }}
    />
    <button
        type="button"
        x-show="query && query.length > 0"
        x-cloak
        @click="clearSearch()"
        class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
        title="{{ __('global.search.clear') }}"
        aria-label="{{ __('global.search.clear') }}"
    >
        <span class="material-symbols-outlined text-[16px]">close</span>
    </button>
</div>
