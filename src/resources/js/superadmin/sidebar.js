/**
 * Superadmin sidebar collapse / expand (desktop), mirroring the admin console's toggleAdminSidebar().
 *
 * The state lives on <html class="sa-sidebar-collapsed">, applied before first paint by an inline
 * script in resources/views/superadmin/layout.blade.php, and is remembered in localStorage.
 */
const STORAGE_KEY = 'df_superadmin_sidebar_collapsed';

const syncToggleIcons = (collapsed) => {
    document.querySelectorAll('[data-sidebar-toggle-icon]').forEach((icon) => {
        icon.textContent = collapsed ? 'dock_to_right' : 'dock_to_left';
    });
    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });
};

/**
 * Keep the collapsed-sidebar tooltip (.superadmin-nav-tooltip, position: fixed) vertically aligned with the
 * hovered or focused menu link, since the nav itself scrolls.
 */
const initNavTooltips = () => {
    const place = (event) => {
        const link = event.target.closest?.('.superadmin-nav a');
        if (!link) return;
        const rect = link.getBoundingClientRect();
        link.style.setProperty('--sa-tooltip-top', `${rect.top + rect.height / 2}px`);
    };
    const nav = document.querySelector('.superadmin-nav');
    nav?.addEventListener('mouseover', place);
    nav?.addEventListener('focusin', place);
};

export function initSuperadminSidebar() {
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    if (toggles.length === 0) return;

    initNavTooltips();

    syncToggleIcons(document.documentElement.classList.contains('sa-sidebar-collapsed'));

    toggles.forEach((button) => button.addEventListener('click', () => {
        const collapsed = document.documentElement.classList.toggle('sa-sidebar-collapsed');
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
        } catch (e) {
            // Storage unavailable (private mode): the toggle still works for this page view.
        }
        syncToggleIcons(collapsed);
    }));
}
