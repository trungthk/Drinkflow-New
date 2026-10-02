/**
 * Confirmation modal for plain forms (markup: resources/views/components/admin/confirm-modal.blade.php).
 *
 * A form with data-confirm-message is held on submit; the modal shows the message (plus the optional
 * data-confirm-title / data-confirm-button texts, and data-confirm-tone="danger" for destructive actions)
 * and the form is only posted after "confirm". Texts come from the Blade attributes, never from this file.
 *
 * @returns {void}
 */
export function initAdminConfirmForms() {
    const modal = document.getElementById('admin-confirm-modal');
    if (!modal || modal.dataset.initialized === 'true') return;
    modal.dataset.initialized = 'true';

    const titleEl = modal.querySelector('[data-confirm-title]');
    const messageEl = modal.querySelector('[data-confirm-message]');
    const okButton = modal.querySelector('[data-confirm-ok]');
    const iconBox = modal.querySelector('[data-confirm-icon]');
    const iconSymbol = modal.querySelector('[data-confirm-icon-symbol]');
    const defaultTitle = titleEl.textContent.trim();
    const toneClasses = {
        danger: { ok: ['bg-error', 'hover:opacity-90'], icon: ['bg-error-container', 'text-error', 'border-error/20'], symbol: 'warning' },
        primary: { ok: ['bg-primary', 'hover:bg-primary-container'], icon: ['bg-primary/10', 'text-primary', 'border-primary/20'], symbol: 'help' },
    };
    let pendingForm = null;
    let lastFocus = null;

    const applyTone = (tone) => {
        Object.values(toneClasses).forEach(({ ok, icon }) => {
            okButton.classList.remove(...ok);
            iconBox.classList.remove(...icon);
        });
        const selected = toneClasses[tone] || toneClasses.primary;
        okButton.classList.add(...selected.ok);
        iconBox.classList.add(...selected.icon);
        iconSymbol.textContent = selected.symbol;
    };

    const open = (form) => {
        pendingForm = form;
        lastFocus = document.activeElement;
        titleEl.textContent = form.dataset.confirmTitle || defaultTitle;
        messageEl.textContent = form.dataset.confirmMessage || '';
        okButton.textContent = form.dataset.confirmButton || okButton.dataset.defaultLabel;
        applyTone(form.dataset.confirmTone);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        okButton.focus();
    };

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingForm = null;
        lastFocus?.focus?.();
    };

    // Capture phase, so the hold happens before other submit handlers (e.g. the submit loading state).
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage || form.dataset.confirmed === 'true') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        open(form);
    }, true);

    okButton.addEventListener('click', () => {
        const form = pendingForm;
        if (!form) return;
        close();
        form.dataset.confirmed = 'true';
        // requestSubmit() fires the submit event again (now confirmed), so the loading state still runs.
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
    });
    modal.querySelectorAll('[data-confirm-cancel], [data-confirm-backdrop]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
}
