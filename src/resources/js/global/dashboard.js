/**
 * Global User Dashboard Interactive Module
 */
export function initGlobalDashboard() {
    const supportModal = document.getElementById('support-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const modalConfirmBtn = document.getElementById('modal-confirm-btn');
    const triggerButtons = document.querySelectorAll('.btn-open-support-modal, [data-open-support-modal]');

    window.openSupportModal = function(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        if (supportModal) {
            supportModal.classList.remove('hidden');
            supportModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
    };

    window.closeSupportModal = function() {
        if (supportModal) {
            supportModal.classList.add('hidden');
            supportModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    };

    triggerButtons.forEach(btn => btn.addEventListener('click', window.openSupportModal));
    if (closeModalBtn) closeModalBtn.addEventListener('click', window.closeSupportModal);
    if (modalConfirmBtn) modalConfirmBtn.addEventListener('click', window.closeSupportModal);

    if (supportModal) {
        supportModal.addEventListener('click', (e) => {
            if (e.target === supportModal) {
                window.closeSupportModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            window.closeSupportModal();
        }
    });
}
