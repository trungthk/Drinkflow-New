/**
 * Public Authentication Modal Interactive Module
 */
export function initAuthModal() {
    const modal = document.getElementById('auth-modal');
    const modalCard = document.getElementById('modal-card');
    const closeBtn = document.getElementById('close-modal-btn');
    const triggerButtons = document.querySelectorAll('.btn-google-sso, [data-open-auth-modal]');

    if (!modal || !modalCard) return;

    window.openAuthModal = function(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        modal.classList.remove('hidden', 'pointer-events-none');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        });
        document.body.style.overflow = 'hidden';
    };

    window.closeAuthModal = function() {
        modal.classList.add('opacity-0');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }, 200);
    };

    triggerButtons.forEach(btn => {
        btn.addEventListener('click', window.openAuthModal);
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', window.closeAuthModal);
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            window.closeAuthModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            window.closeAuthModal();
        }
    });

    // Auto-open modal if there's login error in URL / session indicator
    if (modal.dataset.autoOpen === 'true') {
        window.openAuthModal();
    }
}
