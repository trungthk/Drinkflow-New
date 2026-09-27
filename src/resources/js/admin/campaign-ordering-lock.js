/**
 * Admin "Khóa chiến dịch" toggle: buttons marked `data-ordering-lock-toggle` (with `data-mode`
 * "lock" or "unlock" and the endpoint in `data-url`) open a confirm dialog from
 * <x-admin.campaign-ordering-lock-modal />. The dialog lets the admin choose whether members are
 * notified, then POSTs `{ notify }` and reloads the page to reflect the new state.
 *
 * @returns {void}
 */
export function initCampaignOrderingLock() {
    const modal = document.querySelector('#campaign-ordering-lock-modal');
    const buttons = document.querySelectorAll('[data-ordering-lock-toggle]');
    if (!modal || buttons.length === 0 || modal.dataset.initialized === 'true') return;
    modal.dataset.initialized = 'true';

    let i18n = {};
    try {
        i18n = JSON.parse(modal.dataset.i18n || '{}');
    } catch (error) {
        i18n = {};
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const q = (selector) => modal.querySelector(selector);
    const iconWrap = q('[data-lock-modal-icon-wrap]');
    const icon = q('[data-lock-modal-icon]');
    const title = q('[data-lock-modal-title]');
    const desc = q('[data-lock-modal-desc]');
    const notify = q('[data-lock-modal-notify]');
    const errorEl = q('[data-lock-modal-error]');
    const confirmBtn = q('[data-lock-modal-confirm]');
    const confirmIcon = q('[data-lock-modal-confirm-icon]');
    const confirmLabel = q('[data-lock-modal-confirm-label]');

    const styles = {
        lock: {
            icon: 'lock',
            wrap: ['bg-amber-50', 'border-amber-200', 'text-amber-600', 'dark:bg-amber-950/50', 'dark:border-amber-800', 'dark:text-amber-400'],
            button: ['bg-amber-600', 'hover:bg-amber-700'],
        },
        unlock: {
            icon: 'lock_open',
            wrap: ['bg-emerald-50', 'border-emerald-200', 'text-emerald-600', 'dark:bg-emerald-950/50', 'dark:border-emerald-800', 'dark:text-emerald-400'],
            button: ['bg-emerald-600', 'hover:bg-emerald-700'],
        },
    };

    let url = '';
    let lastTrigger = null;
    let busy = false;

    const showError = (message) => {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
    };

    const open = (button) => {
        const mode = button.dataset.mode === 'unlock' ? 'unlock' : 'lock';
        const text = i18n[mode] || {};
        url = button.dataset.url || '';
        lastTrigger = button;

        Object.values(styles).forEach((style) => {
            iconWrap.classList.remove(...style.wrap);
            confirmBtn.classList.remove(...style.button);
        });
        iconWrap.classList.add(...styles[mode].wrap);
        confirmBtn.classList.add(...styles[mode].button);
        icon.textContent = styles[mode].icon;
        confirmIcon.textContent = styles[mode].icon;
        title.textContent = text.title || '';
        desc.textContent = text.desc || '';
        confirmLabel.textContent = text.confirm || '';
        confirmLabel.dataset.text = text.confirm || '';
        notify.checked = true;
        showError('');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        confirmBtn.focus();
    };

    const close = () => {
        if (busy) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        lastTrigger?.focus();
    };

    const setBusy = (state) => {
        busy = state;
        confirmBtn.disabled = state;
        confirmLabel.textContent = state ? (i18n.processing || '') : (confirmLabel.dataset.text || '');
    };

    const submit = async () => {
        if (busy || !url) return;
        setBusy(true);
        showError('');
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                credentials: 'same-origin',
                body: JSON.stringify({ notify: notify.checked }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || i18n.failed || '');
            }
            window.notify?.(payload.message, 'success');
            window.location.reload();
        } catch (error) {
            setBusy(false);
            showError(error.message || i18n.failed || '');
        }
    };

    buttons.forEach((button) => button.addEventListener('click', () => open(button)));
    modal.querySelectorAll('[data-lock-modal-close], [data-lock-modal-backdrop]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
    confirmBtn.addEventListener('click', submit);
}
