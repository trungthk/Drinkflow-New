{{-- Password field with a show/hide toggle; the click handler lives in resources/js/superadmin/modal.js. --}}
@props([
    'id',
    'name',
    'autocomplete' => 'new-password',
])

<div class="relative">
    <input type="password" id="{{ $id }}" name="{{ $name }}" autocomplete="{{ $autocomplete }}"
        {{ $attributes->merge(['class' => 'w-full px-3 py-2 pr-10 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary']) }}>
    <button type="button" data-password-toggle="{{ $id }}" aria-label="{{ __('admin.toggle_password_visibility') }}" aria-pressed="false"
        class="absolute right-2 top-1/2 -translate-y-1/2 flex text-outline hover:text-primary cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">visibility</span>
    </button>
</div>
