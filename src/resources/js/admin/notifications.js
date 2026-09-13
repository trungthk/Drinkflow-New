/**
 * Admin Webhook Channels & Notifications Controller
 */
export function initAdminNotifications() {
    const form = document.querySelector('#add-channel-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!form && !document.querySelector('[data-notification-channel]')) return;

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const type = document.querySelector('#ch-type')?.value;
        const url = document.querySelector('#ch-url')?.value.trim();
        const token = document.querySelector('#ch-token')?.value.trim();
        const events = Array.from(document.querySelectorAll('input[name="events[]"]:checked')).map(el => el.value);

        try {
            const res = await fetch(`/admin/${roomSlug}/notification-channels`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ type, webhook_url: url, secret_token: token, bot_token: token, events, status: 'active', is_active: true })
            });
            if (res.ok) {
                alert('Saved successfully.');
                window.location.reload();
            } else {
                alert('Could not save webhook.');
            }
        } catch (e) {
            console.error(e);
            alert('Server error.');
        }
    });

    window.testChannel = async function(id) {
        try {
            const res = await fetch(`/admin/${roomSlug}/notification-channels/${id}/test`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) {
                alert('Test ping sent successfully.');
            } else {
                alert('Could not send test ping.');
            }
        } catch (e) {
            console.error(e);
            alert('Server error.');
        }
    };

    window.deleteChannel = async function(id) {
        if (!confirm('Delete this channel?')) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/notification-channels/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) window.location.reload();
            else alert('Could not delete channel.');
        } catch (e) {
            console.error(e);
        }
    };
}
