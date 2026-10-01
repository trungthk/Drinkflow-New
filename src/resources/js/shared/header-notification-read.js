/**
 * Header notification dropdown: clicking an item opens its link (when it has one) and marks it as read when unread.
 *
 * Items carry `data-read-url` and `data-unread="1"`. The PATCH request uses `keepalive` so it still
 * completes when the click also follows the item's "view detail" link.
 */
export function initHeaderNotificationRead() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /** Reflect the server's unread total on the bell badges and "new" pills. */
    const applyUnreadCount = (count) => {
        document.querySelectorAll('[data-user-notification-badge]').forEach((badge) => {
            badge.classList.toggle('hidden', count <= 0);
            badge.classList.toggle('flex', count > 0);
            badge.textContent = count > 9 ? '9+' : String(count);
        });
        document.querySelectorAll('[data-user-notification-new-badge]').forEach((el) => {
            el.classList.toggle('hidden', count <= 0);
            el.textContent = (el.dataset.template || ':count').replace(':count', String(count));
        });
    };

    document.addEventListener('click', (event) => {
        const item = event.target.closest('[data-header-notification-id][data-read-url]');
        if (!item) return;

        // Clicking anywhere on an item with a link opens it (the inner "view detail" anchor navigates by itself).
        const link = item.dataset.notificationLink;
        if (link && !event.target.closest('a')) {
            setTimeout(() => { window.location.href = link; }, 0);
        }

        if (item.dataset.unread !== '1' || !csrfToken) return;

        item.dataset.unread = '0';
        item.classList.remove('bg-emerald-50/20');
        item.querySelector('[data-header-notification-dot]')?.remove();

        fetch(item.dataset.readUrl, {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            credentials: 'same-origin',
            keepalive: true,
        })
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((payload) => applyUnreadCount(Number(payload.unread_count ?? 0)))
            .catch((error) => {
                console.error(error);
                item.dataset.unread = '1';
            });
    });
}
