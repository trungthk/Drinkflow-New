/** Display a browser desktop notification for an authorized realtime user notification. */
export function showDesktopNotification(payload) {
    if (!('Notification' in window)) return;

    const display = () => {
        if (Notification.permission !== 'granted') return;
        const notification = new Notification(payload.title || 'DrinkFlow', {
            body: payload.body || '',
            tag: `drinkflow-notification-${payload.id || Date.now()}`,
            icon: '/favicon.svg'
        });
        notification.onclick = () => {
            window.focus();
            const roomId = payload.data?.room_id;
            if (roomId) window.location.assign(`/rooms/${roomId}/notifications`);
            notification.close();
        };
    };

    if (Notification.permission === 'default') {
        Notification.requestPermission().then((permission) => { if (permission === 'granted') display(); }).catch(() => undefined);
        return;
    }
    display();
}
