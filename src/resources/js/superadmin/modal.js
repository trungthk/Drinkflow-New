/**
 * Generic superadmin modal open/close controller + a shared confirm() replacement.
 *
 * Any element with [data-modal-open="modal-id"] opens that modal; any element inside a modal
 * with [data-modal-close] (including the backdrop) closes it, as does the Escape key.
 */
export function initSuperadminModals() {
    const openModal = (modal) => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.querySelector('input, textarea, select, button[type="submit"]')?.focus();
    };

    const closeModal = (modal) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');
        if (opener) {
            const modal = document.getElementById(opener.dataset.modalOpen);
            if (modal) openModal(modal);
            return;
        }

        const closer = event.target.closest('[data-modal-close]');
        if (closer) {
            const modal = closer.closest('[data-modal]');
            if (modal) closeModal(modal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-modal]:not(.hidden)').forEach((modal) => closeModal(modal));
    });

    window.openSuperadminModal = (id) => {
        const modal = document.getElementById(id);
        if (modal) openModal(modal);
    };
    window.closeSuperadminModal = (id) => {
        const modal = document.getElementById(id);
        if (modal) closeModal(modal);
    };
}

/**
 * Show the shared #confirm-modal (see components/superadmin/confirm-modal.blade.php) in place of
 * a native confirm(), and run `onConfirm` when the user confirms.
 *
 * @param {{message: string, confirmLabel?: string, onConfirm: () => Promise<void>|void}} options
 */
export function openSuperadminConfirm({ message, confirmLabel, onConfirm }) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    modal.querySelector('#confirm-modal-message').textContent = message;
    if (confirmLabel) {
        modal.querySelector('#confirm-modal-submit-label').textContent = confirmLabel;
    }

    const submitButton = modal.querySelector('#confirm-modal-submit');
    const freshButton = submitButton.cloneNode(true); // drop any previous page's listener
    submitButton.replaceWith(freshButton);

    freshButton.addEventListener('click', async () => {
        freshButton.disabled = true;
        freshButton.classList.add('opacity-70', 'cursor-wait');
        try {
            await onConfirm();
            window.closeSuperadminModal('confirm-modal');
        } catch (error) {
            const errorBox = modal.querySelector('#confirm-modal-error');
            if (errorBox) {
                errorBox.textContent = error.message || String(error);
                errorBox.classList.remove('hidden');
            }
        } finally {
            freshButton.disabled = false;
            freshButton.classList.remove('opacity-70', 'cursor-wait');
        }
    });

    window.openSuperadminModal('confirm-modal');
}
