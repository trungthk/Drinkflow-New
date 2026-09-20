/**
 * Room Header Notification & Menu Interactions
 */
export function initRoomHeader() {
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
