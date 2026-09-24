/**
 * Connect room pages to realtime events. Most events never reload the page: user notifications show a desktop
 * notification, and data events are re-dispatched as a window `realtime-event` for pages that update in place.
 * Only access changes reload (device revoked, account deleted, blocked/removed from a room, maintenance).
 */
import { showDesktopNotification } from '../global/desktop-notification';
import { attachSocketDebugLogger } from '../shared/socket-debug';
import { connectGuestRealtime, listenForcedReload } from '../shared/realtime-reload';

const RELOAD_MEMBERSHIP_STATUSES = ['blocked', 'removed'];

export function initRoomRealtime() {
    if (!window.io) return;

    const tokenUrl = document.body.dataset.roomSocketTokenUrl;
    const realtimeUrl = document.body.dataset.realtimeUrl;
    if (!tokenUrl || !realtimeUrl) return;

    fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(async (response) => ({ ok: response.ok, payload: await response.json() }))
        .catch(() => ({ ok: false, payload: null }))
        .then(({ ok, payload }) => {
            const token = payload?.data?.token;
            // No room token: still hear maintenance notices.
            if (!ok || !token) {
                connectGuestRealtime(realtimeUrl, 'room');
                return;
            }

            const socket = window.io(realtimeUrl, { auth: { token }, transports: ['websocket', 'polling'] });
            attachSocketDebugLogger(socket, 'room');
            listenForcedReload(socket);
            [
                'campaign.created', 'campaign.updated', 'campaign.deleted', 'campaign.closed',
                'campaign.menu.updated', 'campaign.menu.deleted',
                'debt.payment_approved', 'order.payment_approved',
            ].forEach((event) => {
                socket.on(event, (eventPayload) => {
                    window.dispatchEvent(new CustomEvent('realtime-event', {
                        detail: { name: event, payload: eventPayload }
                    }));
                });
            });
            socket.on('notification.created', showDesktopNotification);
            socket.on('room.membership.updated', (payload) => {
                // Blocked/removed members lose access: reload so the server shows the right page.
                if (RELOAD_MEMBERSHIP_STATUSES.includes(payload?.status)) window.location.reload();
            });
        });
}
