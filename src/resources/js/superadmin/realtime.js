/**
 * Live platform notifications of the signed-in Superadmin.
 *
 * The layout (resources/views/superadmin/layout.blade.php) exposes body[data-sa-realtime-url] and
 * body[data-sa-socket-token-url]. The socket joins the account's private `superadmin:{id}` channel, so a
 * `superadmin.notification.created` event (e.g. a new Agent registration waiting for review) is shown as a
 * toast and prepended to the header bell without reloading the page. Translations come from the bell's
 * data-i18n attribute.
 */
import { attachSocketDebugLogger } from '../shared/socket-debug';

const parseI18n = (raw) => {
    try {
        return JSON.parse(raw || '{}');
    } catch (error) {
        return {};
    }
};

/**
 * Prepend one notification to the header bell and bump its unread badge.
 *
 * @param {HTMLElement} root [data-sa-notifications] element.
 * @param {{id?: number, type?: string, title?: string, body?: string, data?: object}} payload Event payload.
 * @param {Record<string, string>} i18n Translations from the bell's data-i18n.
 */
function addToBell(root, payload, i18n) {
    const list = root.querySelector('[data-sa-notifications-list]');
    if (!list || payload?.id === undefined) return;
    if (list.querySelector(`[data-sa-notification-item][data-id="${CSS.escape(String(payload.id))}"]`)) return;

    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'sa-bell-item';
    item.dataset.saNotificationItem = '';
    item.dataset.id = String(payload.id);
    // Keep only the path, so the link always stays on this console's origin (APP_URL may differ from it).
    try {
        const url = new URL(String(payload?.data?.url || ''), window.location.origin);
        if (payload?.data?.url) item.dataset.link = `${url.pathname}${url.search}${url.hash}`;
    } catch (error) {
        // Malformed link: the item is still shown, just not clickable through.
    }
    item.innerHTML = `
        <span class="sa-bell-icon"><span class="material-symbols-outlined" data-icon></span></span>
        <span class="sa-bell-text"><strong data-title></strong><small data-body></small><em data-time></em></span>`;
    item.querySelector('[data-icon]').textContent = String(payload?.type || '').startsWith('agent.') ? 'storefront' : 'notifications';
    item.querySelector('[data-title]').textContent = payload?.title || '';
    const body = item.querySelector('[data-body]');
    if (payload?.body) body.textContent = payload.body;
    else body.remove();
    item.querySelector('[data-time]').textContent = i18n.just_now || '';
    list.prepend(item);

    root.querySelector('[data-sa-notifications-empty]')?.setAttribute('hidden', '');
    root.querySelector('[data-sa-mark-all-read]')?.removeAttribute('hidden');
    const badge = root.querySelector('[data-sa-unread-badge]');
    if (badge) {
        const count = (Number.parseInt(badge.textContent, 10) || 0) + 1;
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = false;
    }
}

/** Open the authenticated Superadmin socket and render incoming platform notifications. */
export function initSuperadminRealtime() {
    const { saRealtimeUrl: realtimeUrl, saSocketTokenUrl: tokenUrl } = document.body.dataset;
    const root = document.querySelector('[data-sa-notifications]');
    if (!window.io || !realtimeUrl || !tokenUrl || !root) return;

    const i18n = parseI18n(root.dataset.i18n);
    const fetchToken = () => fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((response) => (response.ok ? response.json() : null))
        .then((json) => json?.data?.token || null)
        .catch(() => null);

    fetchToken().then((token) => {
        if (!token) return;

        let firstToken = token;
        const socket = window.io(realtimeUrl, {
            // Tokens are short-lived: fetch a fresh one on every reconnect instead of replaying an expired one.
            auth: (callback) => {
                if (firstToken) {
                    callback({ token: firstToken });
                    firstToken = null;
                    return;
                }
                fetchToken().then((fresh) => callback(fresh ? { token: fresh } : {}));
            },
            transports: ['websocket', 'polling'],
        });
        attachSocketDebugLogger(socket, 'superadmin');
        socket.on('superadmin.notification.created', (payload) => {
            addToBell(root, payload, i18n);
            const message = payload?.body ? `${payload.title}: ${payload.body}` : payload?.title;
            if (message && typeof window.notify === 'function') window.notify(message, 'info');
        });
    });
}
