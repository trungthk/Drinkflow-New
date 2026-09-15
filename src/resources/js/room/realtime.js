/** Connect room pages to data-changing realtime events and refresh stale server-rendered views. */
import { showDesktopNotification } from '../global/desktop-notification';

export function initRoomRealtime() {
    if (!window.io) return;

    const tokenUrl = document.body.dataset.roomSocketTokenUrl;
    const realtimeUrl = document.body.dataset.realtimeUrl;
    if (!tokenUrl || !realtimeUrl) return;

    fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(async (response) => ({ ok: response.ok, payload: await response.json() }))
        .then(({ ok, payload }) => {
            const token = payload?.data?.token;
            if (!ok || !token) return;

            const socket = window.io(realtimeUrl, { auth: { token }, transports: ['websocket', 'polling'] });
            const refresh = () => window.location.reload();
            [
                'campaign.created', 'campaign.updated', 'campaign.deleted', 'campaign.closed',
                'campaign.menu.updated', 'campaign.menu.deleted'
            ].forEach((event) => socket.on(event, refresh));
            socket.on('notification.created', showDesktopNotification);
        })
        .catch(() => undefined);
}
