{{-- Single shared confirmation modal (delete room/admin/failed-job, …). Pages call
     window.openSuperadminConfirm({ message, description, confirmLabel, confirmIcon, onConfirm }) instead of confirm(). --}}
<x-superadmin.modal id="confirm-modal" icon="warning" :title="__('superadmin.common.confirm_action')" max-width="max-w-md">
    <p id="confirm-modal-message" class="text-xs text-on-surface-variant leading-relaxed mb-3"></p>
    <div id="confirm-modal-description" class="hidden mb-3">
        <div class="flex items-start gap-2 rounded-lg p-2.5 bg-amber-50 border border-amber-200 text-amber-900 text-xs leading-relaxed">
            <span class="material-symbols-outlined text-[16px] shrink-0" aria-hidden="true">info</span>
            <span id="confirm-modal-description-text"></span>
        </div>
    </div>
    <div id="confirm-modal-error" class="hidden mb-3 rounded-lg p-2.5 bg-error-container/60 border border-error/30 text-error text-xs"></div>
    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant">
        <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>
            {{ __('superadmin.common.cancel') }}
        </button>
        <button type="button" id="confirm-modal-submit" class="px-4 py-2 rounded-lg bg-error hover:opacity-90 text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
            <span class="material-symbols-outlined text-[16px]" id="confirm-modal-submit-icon" data-default="delete">delete</span>
            <span id="confirm-modal-submit-label" data-default="{{ __('superadmin.common.delete') }}">{{ __('superadmin.common.delete') }}</span>
        </button>
    </div>
</x-superadmin.modal>
