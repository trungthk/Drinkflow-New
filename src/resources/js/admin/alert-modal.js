/**
 * Shared Admin alert modal: replaces native `alert()` popups with a centered,
 * on-brand modal (icon + message + single OK button) instead of a toast or a
 * blocking browser dialog.
 */

const ICON_STYLES = {
    info: { icon: 'info', classes: 'bg-primary/10 text-primary border-primary/20' },
    success: { icon: 'check_circle', classes: 'bg-primary/10 text-primary border-primary/20' },
    warning: { icon: 'warning', classes: 'bg-amber-100 text-amber-700 border-amber-200' },
    error: { icon: 'error', classes: 'bg-error-container text-error border-error/20' },
};

let elements = null;

function open(message, type) {
    if (!elements) return;
    const { modal, content, icon, iconSymbol, messageEl } = elements;
    const style = ICON_STYLES[type] || ICON_STYLES.info;

    messageEl.textContent = String(message ?? '');
    iconSymbol.textContent = style.icon;
    icon.className = `w-12 h-12 rounded-2xl border flex items-center justify-center mb-4 ${style.classes}`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    });
}

function close() {
    if (!elements) return;
    const { modal, content } = elements;
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}

/** Wire the shared alert modal and make it the admin console's `alert()` and `window.showAdminAlert()`. */
export function initAdminAlertModal() {
    const modal = document.getElementById('admin-alert-modal');
    if (!modal) return;

    elements = {
        modal,
        content: document.getElementById('admin-alert-modal-content'),
        icon: document.getElementById('admin-alert-modal-icon'),
        iconSymbol: document.getElementById('admin-alert-modal-icon-symbol'),
        messageEl: document.getElementById('admin-alert-modal-message'),
    };

    document.getElementById('admin-alert-modal-ok')?.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    window.showAdminAlert = (message, type = 'info') => open(message, type);
    // Every existing `alert(...)` call site in the admin JS bundle now opens this modal instead
    // of a blocking native dialog (or, previously, a toast) — no call site needs to change.
    window.alert = (message) => open(message, 'info');
}
