{{-- Single shared confirmation modal (delete room/admin/failed-job, …). Pages call
     window.openSuperadminConfirm({ message, confirmLabel, danger, onConfirm }) instead of confirm(). --}}
<x-superadmin.modal id="confirm-modal" icon="warning" :title="__('superadmin.common.confirm_action')" max-width="max-w-md">
    <p id="confirm-modal-message" class="text-xs text-on-surface-variant leading-relaxed mb-3"></p>
    <div id="confirm-modal-error" class="hidden mb-3 rounded-lg p-2.5 bg-error-container/60 border border-error/30 text-error text-xs"></div>
    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant">
        <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors" data-modal-close>
            {{ __('superadmin.common.cancel') }}
        </button>
        <button type="button" id="confirm-modal-submit" class="px-4 py-2 rounded-lg bg-error hover:opacity-90 text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
            <span class="material-symbols-outlined text-[16px]">delete</span>
            <span id="confirm-modal-submit-label">{{ __('superadmin.common.delete') }}</span>
        </button>
    </div>
</x-superadmin.modal>
