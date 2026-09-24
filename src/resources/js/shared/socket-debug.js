/**
 * Console logging of realtime (Socket.IO) traffic for local testing.
 *
 * Enabled only when the layout renders `data-socket-debug` on <body>, which the admin, global (/me) and
 * room layouts do when APP_ENV=local, so staging/production consoles stay quiet.
 */

/**
 * Whether socket debug logging is enabled for the current page.
 *
 * @returns {boolean}
 */
export function isSocketDebugEnabled() {
    return document.body?.dataset.socketDebug !== undefined;
}

/**
 * Log every incoming event plus connection lifecycle of a socket to the browser console.
 *
 * @param {import('socket.io-client').Socket} socket Connected (or connecting) Socket.IO client.
 * @param {string} scope Short label identifying the connection, e.g. "admin", "user", "room".
 * @returns {void}
 */
export function attachSocketDebugLogger(socket, scope) {
    if (!socket || !isSocketDebugEnabled()) return;

    const prefix = `[socket:${scope}]`;
    const time = () => new Date().toLocaleTimeString();

    socket.on('connect', () => console.info(`${prefix} ${time()} connected`, { id: socket.id }));
    socket.on('disconnect', (reason) => console.warn(`${prefix} ${time()} disconnected`, reason));
    socket.on('connect_error', (error) => console.error(`${prefix} ${time()} connect_error`, error?.message ?? error));
    socket.onAny((event, ...args) => {
        console.groupCollapsed(`${prefix} ${time()} ← ${event}`);
        args.forEach((arg) => console.log(arg));
        console.groupEnd();
    });
}
