/**
 * Connect the authenticated browser device (/me pages) to realtime events: desktop notifications, plus the
 * few events that force a reload (device revoked, account deleted, blocked/removed from a room, maintenance).
 * Other events never reload the page.
 */
import { showDesktopNotification } from './desktop-notification';
import { attachSocketDebugLogger } from '../shared/socket-debug';
import { connectGuestRealtime, listenForcedReload } from '../shared/realtime-reload';

const RELOAD_MEMBERSHIP_STATUSES = ['blocked', 'removed'];

export async function initSessionRevocation() {
    if (!window.io) return;
    const tokenUrl = document.body.dataset.socketTokenUrl;
    const realtimeUrl = document.body.dataset.realtimeUrl;
    if (!tokenUrl || !realtimeUrl) return;

    let token = null;
    try {
        const response = await fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        token = response.ok ? (await response.json())?.data?.token : null;
    } catch (error) {
        console.error('Realtime token request failed.', error);
    }

    // No trusted-device token (e.g. no device cookie yet): still hear maintenance notices.
    if (!token) {
        connectGuestRealtime(realtimeUrl, 'user');
        return;
    }

    const socket = window.io(realtimeUrl, { auth: { token }, transports: ['websocket', 'polling'] });
    attachSocketDebugLogger(socket, 'user');
    listenForcedReload(socket);
    socket.on('notification.created', showDesktopNotification);
    socket.on('room.membership.updated', (payload) => {
        if (RELOAD_MEMBERSHIP_STATUSES.includes(payload?.status)) window.location.reload();
    });
}
