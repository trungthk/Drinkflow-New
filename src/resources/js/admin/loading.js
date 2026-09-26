import { renderTableSkeleton } from './ui-enhancements';

/** Initialize the shared page-navigation and form-loading overlay for admin pages. */
export function initAdminLoading() {
    const overlay = document.getElementById('admin-page-loading');
    const card = document.getElementById('admin-loading-card');
    if (!overlay || !card || overlay.dataset.initialized) return;
    overlay.dataset.initialized = 'true';
    let isInternalNavigation = false;

    const show = () => {
        overlay.classList.remove('hidden', 'pointer-events-none', 'opacity-0');
        overlay.classList.add('flex', 'opacity-100');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    };
    const hide = () => {
        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        window.setTimeout(() => { overlay.classList.add('hidden', 'pointer-events-none'); overlay.classList.remove('flex'); }, 200);
    };

    window.showAdminLoading = show;
    window.hideAdminLoading = hide;
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const href = link.getAttribute('href') || '';
        if (link.target === '_blank' || link.hasAttribute('download') || href === '' || href.startsWith('#') || href.startsWith('javascript:')) return;
        const destination = new URL(link.href, window.location.href);
        if (destination.origin === window.location.origin && destination.href !== window.location.href) {
            isInternalNavigation = true;
            show();
        }
    });
    window.addEventListener('pageshow', hide);
    window.addEventListener('beforeunload', () => {
        if (isInternalNavigation) show();
    });
}

/** Placeholder bars shown in a [data-skeleton="chart"] area while the page reloads. */
function chartSkeletonHtml() {
    const heights = [45, 70, 35, 85, 55, 65, 40];
    return `
        <div class="admin-chart-skeleton h-full w-full animate-pulse flex flex-col justify-end gap-3 px-4 pb-2" aria-hidden="true">
            <div class="flex-1 flex items-end justify-around gap-4 border-b border-outline-variant/60">
                ${heights.map(h => `<div class="w-8 rounded-t bg-surface-container-high" style="height:${h}%"></div>`).join('')}
            </div>
            <div class="flex justify-around gap-4">
                ${heights.map(() => '<div class="h-2.5 w-10 rounded bg-surface-container"></div>').join('')}
            </div>
        </div>
    `;
}

/**
 * Replace the data areas of the page with skeletons while it reloads:
 * table[data-skeleton="table"] gets skeleton rows, [data-skeleton="chart"] gets placeholder bars.
 *
 * @param {ParentNode} root Where to look for skeleton targets.
 */
export function showContentSkeletons(root = document) {
    root.querySelectorAll('table[data-skeleton="table"]').forEach((table) => {
        const tbody = table.tBodies[0];
        if (!tbody) return;
        const cols = table.tHead?.rows[0]?.cells.length || 5;
        const rows = Math.min(Math.max(tbody.rows.length, 3), 8);
        renderTableSkeleton(tbody, cols, rows);
        table.setAttribute('aria-busy', 'true');
    });
    root.querySelectorAll('[data-skeleton="chart"]').forEach((chart) => {
        chart.innerHTML = chartSkeletonHtml();
        chart.setAttribute('aria-busy', 'true');
    });
}

/** Show skeletons when a filter form ([data-skeleton-on-submit]) is submitted. */
export function initFilterFormSkeletons() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-skeleton-on-submit') || event.defaultPrevented) return;
        showContentSkeletons();
    });

    // A page restored from the back/forward cache would keep its skeletons: load it fresh instead.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted && document.querySelector('[data-skeleton][aria-busy="true"]')) window.location.reload();
    });
}

/** Wire every [data-reload-page] button to reload the current page with a spinner while it loads. */
export function initAdminReloadButtons() {
    const setLoading = (button, loading) => {
        button.disabled = loading;
        button.setAttribute('aria-busy', loading ? 'true' : 'false');
        button.querySelector('[data-reload-icon]')?.classList.toggle('animate-spin', loading);
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-reload-page]');
        if (!button || button.disabled) return;
        setLoading(button, true);
        showContentSkeletons();
        window.location.reload();
    });

    // Restore the buttons when the page comes back from the back/forward cache.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-reload-page]').forEach((button) => setLoading(button, false));
    });
}
