/**
 * Superadmin notification inbox: header bell dropdown + "mark as read" actions on the notifications page.
 *
 * Markup: resources/views/components/superadmin/notification-bell.blade.php (header) and the
 * [data-sa-inbox] section of resources/views/superadmin/notifications.blade.php. Every request only
 * touches the signed-in account's own notifications (enforced server-side by AdminNotificationController).
 */
import { dfApi } from './shared';

const setBadge = (root, count) => {
    const badge = root?.querySelector('[data-sa-unread-badge]');
    if (!badge) return;
    badge.textContent = count > 99 ? '99+' : String(count);
    badge.hidden = count < 1;
};

export function initSuperadminNotifications() {
    const root = document.querySelector('[data-sa-notifications]');
    if (!root) return;

    const toggle = root.querySelector('[data-sa-notifications-toggle]');
    const menu = root.querySelector('[data-sa-notifications-menu]');
    const list = root.querySelector('[data-sa-notifications-list]');
    const empty = root.querySelector('[data-sa-notifications-empty]');
    const markAllButton = root.querySelector('[data-sa-mark-all-read]');

    const setOpen = (open) => {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };
    toggle.addEventListener('click', () => setOpen(menu.hidden));
    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });

    const syncEmptyState = () => {
        const hasItems = !!list.querySelector('[data-sa-notification-item]');
        empty.hidden = hasItems;
        if (!hasItems) markAllButton.hidden = true;
    };

    // Page rows (notifications page) that mirror a dropdown item flip to "read" too.
    const markPageRowsRead = (ids = null) => {
        document.querySelectorAll('[data-sa-inbox-row][data-unread]').forEach((row) => {
            if (ids && !ids.includes(row.dataset.id)) return;
            row.removeAttribute('data-unread');
            row.querySelector('[data-sa-inbox-read]')?.remove();
        });
    };

    const markRead = async (id) => {
        const url = root.dataset.readUrlTemplate.replace('__ID__', encodeURIComponent(id));
        const { data } = await dfApi(url, { method: 'PATCH' });
        list.querySelector(`[data-sa-notification-item][data-id="${CSS.escape(id)}"]`)?.remove();
        markPageRowsRead([id]);
        setBadge(root, data.unread_count);
        syncEmptyState();
    };

    list.addEventListener('click', (event) => {
        const item = event.target.closest('[data-sa-notification-item]');
        if (item) markRead(item.dataset.id).catch((error) => console.error(error));
    });

    const markAll = async (button) => {
        button.disabled = true;
        try {
            await dfApi(root.dataset.markAllUrl, { method: 'POST' });
            list.querySelectorAll('[data-sa-notification-item]').forEach((item) => item.remove());
            markPageRowsRead();
            setBadge(root, 0);
            syncEmptyState();
            document.querySelectorAll('[data-sa-inbox-mark-all]').forEach((b) => { b.hidden = true; });
        } catch (error) {
            console.error(error);
        } finally {
            button.disabled = false;
        }
    };

    markAllButton.addEventListener('click', () => markAll(markAllButton));

    // Notifications page: per-row "mark as read" and the section's "mark all as read" button.
    document.addEventListener('click', (event) => {
        const rowButton = event.target.closest('[data-sa-inbox-read]');
        if (rowButton) {
            rowButton.disabled = true;
            markRead(rowButton.closest('[data-sa-inbox-row]').dataset.id).catch((error) => {
                rowButton.disabled = false;
                console.error(error);
            });
            return;
        }
        const allButton = event.target.closest('[data-sa-inbox-mark-all]');
        if (allButton) markAll(allButton);
    });
}
