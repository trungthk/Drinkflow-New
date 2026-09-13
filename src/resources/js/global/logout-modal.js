/**
 * Global Logout Confirmation Modal Module
 */
export function initLogoutModal() {
    const modal = document.getElementById('logout-confirm-modal');
    const content = document.getElementById('logout-modal-content');
    const triggerBtns = document.querySelectorAll('.btn-open-logout-modal, [data-open-logout-modal]');

    window.openLogoutModal = function(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        if (!modal || !content) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        });
    };

    window.closeLogoutModal = function() {
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    };

    triggerBtns.forEach(btn => {
        btn.addEventListener('click', window.openLogoutModal);
    });

    modal?.addEventListener('click', (e) => {
        if (e.target === modal) {
            window.closeLogoutModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            window.closeLogoutModal();
        }
    });
}
