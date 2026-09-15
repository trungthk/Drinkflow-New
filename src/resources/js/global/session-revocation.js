/** Connect the authenticated browser device to realtime forced-logout events. */
import { showDesktopNotification } from './desktop-notification';

export async function initSessionRevocation() {
    if (!window.io) return;
    const tokenUrl = document.body.dataset.socketTokenUrl;
    const realtimeUrl = document.body.dataset.realtimeUrl;
    if (!tokenUrl || !realtimeUrl) return;

    try {
        const response = await fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const token = (await response.json())?.data?.token;
        if (!response.ok || !token) return;
        const socket = window.io(realtimeUrl, { auth: { token }, transports: ['websocket', 'polling'] });
        socket.on('user.session_revoked', () => {
            socket.disconnect();
            window.location.assign('/');
        });
        socket.on('notification.created', showDesktopNotification);
        socket.on('room.membership.updated', (payload) => {
            if (payload?.status === 'removed') {
                socket.disconnect();
                window.location.assign('/me');
                return;
            }

            window.location.reload();
        });
    } catch (error) {
        console.error('Realtime session revocation connection failed.', error);
    }
}
