import { renderSubmitLoading } from '../shared/submit-loading';

/**
 * Show or mask the password input a [data-password-toggle] button points to.
 *
 * @param {HTMLElement} button Toggle button (see components/superadmin/password-input.blade.php).
 * @param {boolean} visible Whether the password should be shown as plain text.
 */
const setPasswordVisible = (button, visible) => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
    const icon = button.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = visible ? 'visibility_off' : 'visibility';
};

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
        // Never leave a typed password visible for the next time the modal opens.
        modal.querySelectorAll('[data-password-toggle]').forEach((button) => setPasswordVisible(button, false));
    };

    document.addEventListener('click', (event) => {
        const passwordToggle = event.target.closest('[data-password-toggle]');
        if (passwordToggle) {
            setPasswordVisible(passwordToggle, passwordToggle.getAttribute('aria-pressed') !== 'true');
            return;
        }

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
 * `description` adds a highlighted note explaining the consequences; `confirmLabel` / `confirmIcon`
 * override the default "Delete" button for this call only.
 *
 * @param {{message: string, description?: string, confirmLabel?: string, confirmIcon?: string, onConfirm: () => Promise<void>|void}} options
 */
export function openSuperadminConfirm({ message, description, confirmLabel, confirmIcon, onConfirm }) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    modal.querySelector('#confirm-modal-message').textContent = message;
    const descriptionBox = modal.querySelector('#confirm-modal-description');
    if (descriptionBox) {
        descriptionBox.querySelector('#confirm-modal-description-text').textContent = description || '';
        descriptionBox.classList.toggle('hidden', !description);
    }
    modal.querySelector('#confirm-modal-error')?.classList.add('hidden');
    const label = modal.querySelector('#confirm-modal-submit-label');
    label.textContent = confirmLabel || label.dataset.default;
    const icon = modal.querySelector('#confirm-modal-submit-icon');
    if (icon) icon.textContent = confirmIcon || icon.dataset.default;

    const submitButton = modal.querySelector('#confirm-modal-submit');
    const freshButton = submitButton.cloneNode(true); // drop any previous page's listener
    submitButton.replaceWith(freshButton);

    freshButton.addEventListener('click', async () => {
        const originalHtml = freshButton.innerHTML;
        freshButton.disabled = true;
        freshButton.classList.add('opacity-70', 'cursor-wait');
        renderSubmitLoading(freshButton);
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
            freshButton.innerHTML = originalHtml;
        }
    });

    window.openSuperadminModal('confirm-modal');
}
