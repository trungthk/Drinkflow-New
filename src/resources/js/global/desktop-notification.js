/**
 * Browser desktop notifications (with a short chime) for realtime user notifications, plus the header
 * on/off toggle (resources/views/components/global/desktop-notify-toggle.blade.php).
 *
 * Desktop notifications are shown only when the browser grants permission AND the user left the toggle on.
 * The choice is remembered per browser (desktop notifications are a per-device setting). Turning the toggle on
 * asks the browser/OS for permission; if the browser blocks notifications, the toggle turns red and explains how
 * to allow them.
 *
 * Clicking a notification opens its own link (payload.link) or, when it has none, the page's notifications list
 * from <body data-notifications-url>.
 */

const PREFERENCE_KEY = 'df_desktop_notifications';
const STATE_ICONS = { on: 'notifications_active', off: 'notifications_off', blocked: 'notifications_off' };

let audioContext = null;
// Fallback when localStorage is unavailable, so toggling still works on the current page.
let sessionPreference = null;

const isSupported = () => 'Notification' in window;

const readPreference = () => {
    try {
        return window.localStorage.getItem(PREFERENCE_KEY);
    } catch (error) {
        return null;
    }
};

const writePreference = (value) => {
    try {
        window.localStorage.setItem(PREFERENCE_KEY, value);
    } catch (error) {
        // Storage unavailable (private mode): the choice lasts for this page view only.
    }
    sessionPreference = value;
};

/**
 * Current toggle state: "on", "off", or "blocked" (the browser denies notifications).
 *
 * @returns {'on'|'off'|'blocked'}
 */
function currentState() {
    if (!isSupported()) return 'off';
    if (Notification.permission === 'denied') return 'blocked';
    const preference = readPreference() ?? sessionPreference;
    // Permission already granted and no explicit choice yet: treat as on.
    return Notification.permission === 'granted' && preference !== 'off' ? 'on' : 'off';
}

const getAudioContext = () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return null;
    audioContext ??= new AudioContextClass();
    return audioContext;
};

/** Play a short two-note chime through Web Audio (no audio file needed). */
function playChime() {
    const context = getAudioContext();
    if (!context) return;
    if (context.state === 'suspended') context.resume().catch(() => undefined);

    const start = context.currentTime;
    [[880, 0], [1318.5, 0.14]].forEach(([frequency, offset]) => {
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0.0001, start + offset);
        gain.gain.exponentialRampToValueAtTime(0.35, start + offset + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + offset + 0.45);
        oscillator.connect(gain).connect(context.destination);
        oscillator.start(start + offset);
        oscillator.stop(start + offset + 0.5);
    });
}

/**
 * Resolve the URL a notification click should open; only http(s) targets are allowed.
 *
 * @param {string|null|undefined} link Notification link (relative or absolute).
 * @returns {string|null} Absolute URL, or null when missing/unsafe.
 */
function resolveLink(link) {
    const candidates = [link, document.body?.dataset.notificationsUrl];
    for (const candidate of candidates) {
        if (!candidate) continue;
        try {
            const url = new URL(candidate, window.location.origin);
            if (url.protocol === 'http:' || url.protocol === 'https:') return url.href;
        } catch (error) {
            // Malformed link: fall through to the next candidate.
        }
    }
    return null;
}

const parseJson = (value) => {
    try {
        return JSON.parse(value || '{}');
    } catch (error) {
        return {};
    }
};

/** Reflect the current state on every toggle button (icon, colours, label, aria-pressed). */
function renderToggles() {
    const state = currentState();
    document.querySelectorAll('[data-desktop-notify-toggle]').forEach((button) => {
        const i18n = parseJson(button.dataset.i18n);
        const stateClasses = parseJson(button.dataset.stateClasses);
        Object.values(stateClasses).forEach((classes) => button.classList.remove(...String(classes).split(/\s+/).filter(Boolean)));
        button.classList.add(...String(stateClasses[state] || '').split(/\s+/).filter(Boolean));

        const label = { on: i18n.disable, off: i18n.enable, blocked: i18n.blocked }[state] || '';
        button.title = label;
        button.setAttribute('aria-label', label);
        button.setAttribute('aria-pressed', state === 'on' ? 'true' : 'false');
        button.dataset.state = state;

        const icon = button.querySelector('[data-desktop-notify-icon]');
        if (icon) icon.textContent = STATE_ICONS[state];
    });
}

const toast = (message, type) => {
    if (message && typeof window.notify === 'function') window.notify(message, type);
};

/**
 * Handle a toggle click: turn off, or turn on by asking the browser/OS for permission.
 *
 * @param {HTMLElement} button The clicked toggle.
 */
async function handleToggle(button) {
    const i18n = parseJson(button.dataset.i18n);
    const state = currentState();

    if (state === 'on') {
        writePreference('off');
        renderToggles();
        toast(i18n.disabled_toast, 'info');
        return;
    }

    let permission = Notification.permission;
    if (permission === 'default') {
        try {
            permission = await Notification.requestPermission();
        } catch (error) {
            permission = Notification.permission;
        }
    }

    if (permission === 'granted') {
        writePreference('on');
        renderToggles();
        playChime();
        toast(i18n.enabled_toast, 'success');
        return;
    }

    renderToggles();
    if (permission === 'denied') toast(i18n.blocked_toast, 'warning');
}

/**
 * Wire the header toggle(s) and unlock the chime on the user's first interaction (browsers only allow
 * audio after a user gesture). Safe to call more than once.
 */
export function initDesktopNotifications() {
    if (initDesktopNotifications.done) return;
    initDesktopNotifications.done = true;

    const unlock = () => {
        const context = getAudioContext();
        if (context?.state === 'suspended') context.resume().catch(() => undefined);
    };
    ['pointerdown', 'keydown'].forEach((type) => document.addEventListener(type, unlock, { once: true, capture: true }));

    const toggles = document.querySelectorAll('[data-desktop-notify-toggle]');
    if (!isSupported() || toggles.length === 0) return;

    toggles.forEach((button) => {
        button.style.removeProperty('display');
        button.addEventListener('click', () => handleToggle(button));
    });
    renderToggles();

    // Keep the icon in sync when permission changes in browser settings or the choice changes in another tab.
    window.addEventListener('storage', (event) => { if (event.key === PREFERENCE_KEY) renderToggles(); });
    navigator.permissions?.query({ name: 'notifications' })
        .then((status) => { status.onchange = renderToggles; })
        .catch(() => undefined);
}

/**
 * Show a desktop notification with a chime for an authorized realtime user notification,
 * unless the user turned desktop notifications off.
 *
 * @param {{id?: number, title?: string, body?: string, link?: string|null}} payload `notification.created` payload.
 */
export function showDesktopNotification(payload) {
    if (currentState() !== 'on') return;
    playChime();

    const notification = new Notification(payload?.title || 'DrinkFlow', {
        body: payload?.body || '',
        tag: `drinkflow-notification-${payload?.id || Date.now()}`,
        icon: '/favicon.svg',
        silent: false,
    });
    notification.onclick = (event) => {
        event.preventDefault();
        window.focus();
        const target = resolveLink(payload?.link);
        if (target) window.location.assign(target);
        notification.close();
    };
}
