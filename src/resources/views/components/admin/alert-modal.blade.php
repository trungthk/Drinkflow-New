<!-- Shared Admin Alert Modal: replaces native alert() popups. Populated/opened via window.showAdminAlert(message, type) in resources/js/admin/alert-modal.js. -->
<div id="admin-alert-modal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-200" role="alertdialog" aria-modal="true" aria-labelledby="admin-alert-modal-title">
    <div class="w-full max-w-md bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline-variant/80 overflow-hidden transform transition-all duration-200 scale-95 opacity-0" id="admin-alert-modal-content">
        <div class="p-6">
            <div id="admin-alert-modal-icon" class="w-12 h-12 rounded-2xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[24px]" id="admin-alert-modal-icon-symbol">info</span>
            </div>
            <h3 class="text-lg font-bold text-on-surface tracking-tight" id="admin-alert-modal-title">{{ __('admin.alert_modal_title') }}</h3>
            <p class="text-sm text-outline mt-2 leading-relaxed [overflow-wrap:anywhere] whitespace-pre-line" id="admin-alert-modal-message"></p>
        </div>
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-end">
            <button type="button" id="admin-alert-modal-ok" class="px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary-container rounded-xl shadow-xs transition-colors cursor-pointer">
                {{ __('admin.alert_modal_ok') }}
            </button>
        </div>
    </div>
</div>
