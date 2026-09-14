<!-- Admin Logout Confirmation Modal -->
<div id="admin-logout-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="admin-logout-modal-title">
    <div class="w-full max-w-md bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline-variant/80 overflow-hidden transform transition-all duration-200 scale-95 opacity-0" id="admin-logout-modal-content">
        <div class="p-6">
            <div class="w-12 h-12 rounded-2xl bg-error-container text-error border border-error/20 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[24px]">logout</span>
            </div>
            <h3 class="text-lg font-bold text-on-surface tracking-tight" id="admin-logout-modal-title">{{ __('admin.logout_modal_title') }}</h3>
            <p class="text-sm text-outline mt-2 leading-relaxed">
                {{ __('admin.logout_modal_desc') }}
            </p>
        </div>
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-end gap-3">
            <button type="button" onclick="closeAdminLogoutModal()" class="px-4 py-2 text-sm font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container rounded-xl transition-colors cursor-pointer">
                {{ __('admin.logout_modal_cancel') }}
            </button>
            <form id="admin-logout-modal-form" action="{{ route('admin.logout') }}" method="POST" class="m-0 p-0">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-error hover:bg-red-700 rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">logout</span>
                    <span>{{ __('admin.logout_modal_confirm') }}</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openAdminLogoutModal(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        const modal = document.getElementById('admin-logout-confirm-modal');
        const content = document.getElementById('admin-logout-modal-content');
        if (!modal || !content) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        });
    }

    function closeAdminLogoutModal() {
        const modal = document.getElementById('admin-logout-confirm-modal');
        const content = document.getElementById('admin-logout-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('admin-logout-confirm-modal');
        const triggerBtns = document.querySelectorAll('.btn-admin-logout, [data-admin-logout]');

        triggerBtns.forEach(btn => {
            btn.addEventListener('click', openAdminLogoutModal);
        });

        modal?.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeAdminLogoutModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeAdminLogoutModal();
            }
        });
    });
</script>
