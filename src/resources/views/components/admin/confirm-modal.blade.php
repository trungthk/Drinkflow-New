{{-- Shared confirmation modal for plain forms (replaces native confirm()). A form opts in with
     data-confirm-message (and optionally data-confirm-title, data-confirm-button, data-confirm-tone="danger");
     resources/js/admin/confirm-form.js opens this modal on submit and posts the form only once confirmed. --}}
<div id="admin-confirm-modal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-modal-title">
    <div data-confirm-backdrop class="absolute inset-0"></div>
    <div class="relative w-full max-w-md bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline-variant/80 overflow-hidden">
        <div class="p-6">
            <div data-confirm-icon class="w-12 h-12 rounded-2xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[24px]" data-confirm-icon-symbol>help</span>
            </div>
            <h3 class="text-lg font-bold text-on-surface tracking-tight" id="admin-confirm-modal-title" data-confirm-title>{{ __('admin.confirm_modal_title') }}</h3>
            <p class="text-sm text-outline mt-2 leading-relaxed [overflow-wrap:anywhere] whitespace-pre-line" data-confirm-message></p>
        </div>
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-end gap-2">
            <button type="button" data-confirm-cancel class="px-4 py-2 text-sm font-semibold text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container transition-colors cursor-pointer">
                {{ __('admin.cancel') }}
            </button>
            <button type="button" data-confirm-ok data-default-label="{{ __('admin.confirm_modal_ok') }}" class="px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary-container rounded-xl shadow-xs transition-colors cursor-pointer">
                {{ __('admin.confirm_modal_ok') }}
            </button>
        </div>
    </div>
</div>
