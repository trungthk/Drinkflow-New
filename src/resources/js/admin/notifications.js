/**
 * Admin Webhook Channels & Notifications Controller
 */
export function initAdminNotifications() {
    const form = document.querySelector('#add-channel-form');
    const typeSelect = document.querySelector('#ch-type');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!form && !document.querySelector('[data-notification-channel]')) return;

    // Platform switcher logic
    function switchPlatform(type) {
        document.querySelectorAll('.platform-config-fields').forEach(el => {
            el.classList.add('hidden');
        });
        const activeContainer = document.querySelector(`#platform-${type}`);
        if (activeContainer) {
            activeContainer.classList.remove('hidden');
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', (e) => {
            switchPlatform(e.target.value);
        });
        switchPlatform(typeSelect.value);
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const type = typeSelect?.value || 'webhook';
        const name = document.querySelector('#ch-name')?.value.trim();

        let config = {};
        if (type === 'telegram') {
            config = {
                bot_token: document.querySelector('#ch-tg-token')?.value.trim() || '',
                chat_id: document.querySelector('#ch-tg-chat-id')?.value.trim() || '',
            };
        } else if (type === 'slack') {
            config = {
                webhook_url: document.querySelector('#ch-slack-url')?.value.trim() || '',
            };
        } else if (type === 'chatwork') {
            config = {
                api_token: document.querySelector('#ch-cw-token')?.value.trim() || '',
                room_id: document.querySelector('#ch-cw-room-id')?.value.trim() || '',
            };
        } else if (type === 'webhook') {
            config = {
                webhook_url: document.querySelector('#ch-wh-url')?.value.trim() || '',
                secret_token: document.querySelector('#ch-wh-secret')?.value.trim() || '',
            };
        }

        try {
            const res = await fetch(`/admin/${roomSlug}/notification-channels`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ type, name, status: 'enabled', config })
            });
            const data = await res.json();
            if (res.ok) {
                alert('Saved successfully.');
                window.location.reload();
            } else {
                alert(data.message || 'Could not save webhook.');
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
            const data = await res.json();
            if (res.ok) {
                alert('Test ping sent successfully.');
            } else {
                alert(data.message || 'Could not send test ping.');
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
