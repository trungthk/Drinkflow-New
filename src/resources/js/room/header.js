import { initHeaderNotificationRead } from '../shared/header-notification-read';

/**
 * Room Header Notification & Menu Interactions
 */
export function initRoomHeader() {
    initHeaderNotificationRead();
    scrollActiveTabIntoView();

    const markAllReadBtn = document.getElementById('room-mark-all-read-btn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async function() {
            const endpoint = markAllReadBtn.dataset.readAllUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!endpoint || !csrfToken || markAllReadBtn.disabled) return;

            markAllReadBtn.disabled = true;
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error('Unable to mark notifications as read.');

                const badges = document.querySelectorAll('[data-user-notification-badge]');
                badges.forEach((b) => {
                    b.classList.add('hidden');
                    b.textContent = '0';
                });

                const allReadText = markAllReadBtn.dataset.readText || '';
                markAllReadBtn.textContent = allReadText;
                markAllReadBtn.classList.add('opacity-50', 'pointer-events-none');
            } catch (error) {
                console.error(error);
                markAllReadBtn.disabled = false;
            }
        });
    }
}

/**
 * On narrow screens the room tab bar scrolls horizontally: bring the active tab into view (centered) on page load.
 * Only the tab bar's own scrollLeft changes, so the page itself never scrolls vertically.
 */
function scrollActiveTabIntoView() {
    const nav = document.querySelector('[data-room-tabs]');
    const active = nav?.querySelector('[aria-current="page"]');
    if (!nav || !active || nav.scrollWidth <= nav.clientWidth) return;

    const navBox = nav.getBoundingClientRect();
    const tabBox = active.getBoundingClientRect();
    const tabLeft = tabBox.left - navBox.left + nav.scrollLeft;
    const target = tabLeft - (nav.clientWidth - tabBox.width) / 2;
    nav.scrollLeft = Math.max(0, Math.min(target, nav.scrollWidth - nav.clientWidth));
}
