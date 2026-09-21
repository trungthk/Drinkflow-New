/**
 * Global notifications page: mark a single notification as read without reloading the page.
 *
 * The per-notification <form data-notification-read-form> keeps working without JavaScript; with it, the request is
 * sent in the background and the card, the "unread" tab counter and the header bell/dropdown are updated in place.
 */
export function initGlobalNotifications() {
    const list = document.querySelector('[data-notification-list]');
    if (!list) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const readLabel = list.dataset.readLabel || '';
    const errorLabel = list.dataset.errorLabel || '';
    const readBadgeClass = 'inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500';

    /** Reflect the server's unread total in every place that shows it. */
    const applyUnreadCount = (count) => {
        document.querySelectorAll('[data-user-notification-badge]').forEach((badge) => {
            badge.classList.toggle('hidden', count <= 0);
            badge.classList.toggle('flex', count > 0);
            badge.textContent = count > 9 ? '9+' : String(count);
        });
        document.querySelectorAll('[data-unread-count]').forEach((el) => { el.textContent = String(count); });
        document.querySelectorAll('[data-user-notification-new-badge]').forEach((el) => {
            el.classList.toggle('hidden', count <= 0);
            el.textContent = (el.dataset.template || ':count').replace(':count', String(count));
        });
    };

    /** Turn one card (and its header dropdown twin) into the "read" state. */
    const applyReadState = (card, id) => {
        card.querySelector('[data-unread-accent]')?.remove();
        const badge = card.querySelector('[data-status-badge]');
        if (badge) {
            badge.className = readBadgeClass;
            badge.textContent = readLabel;
        }
        card.querySelector('[data-notification-actions]')?.remove();

        const headerItem = document.querySelector(`[data-header-notification-id="${id}"]`);
        headerItem?.classList.remove('bg-emerald-50/20');
        headerItem?.querySelector('[data-header-notification-dot]')?.remove();
    };

    const setBusy = (button, busy) => {
        button.disabled = busy;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
        button.classList.toggle('opacity-60', busy);
        button.classList.toggle('cursor-wait', busy);
        const spinner = button.querySelector('[data-read-spinner]');
        spinner?.classList.toggle('hidden', !busy);
    };

    list.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-notification-read-form]');
        if (!form) return;
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const card = form.closest('[data-notification-card]');
        if (!button || !card || button.disabled) return;

        setBusy(button, true);
        form.querySelector('[data-read-error]')?.remove();
        try {
            const response = await fetch(form.action, {
                method: 'PATCH',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();

            applyReadState(card, card.dataset.notificationId);
            applyUnreadCount(Number(payload.unread_count ?? 0));
        } catch (error) {
            console.error(error);
            setBusy(button, false);
            const message = document.createElement('span');
            message.dataset.readError = 'true';
            message.className = 'block text-[11px] text-rose-600 mt-1';
            message.textContent = errorLabel;
            form.appendChild(message);
        }
    });
}
