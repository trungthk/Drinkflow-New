import { renderSubmitLoading } from '../shared/submit-loading';

/**
 * Superadmin Page-Navigation & Submit Loading Overlay Module.
 *
 * Mirrors resources/js/global/loading.js so the superadmin section shares the same
 * "loading page" / "submit loading" UX as the rest of the app instead of having none.
 */
export function initSuperadminLoading() {
    const loadingOverlay = document.getElementById('superadmin-page-loading');
    const loadingCard = document.getElementById('superadmin-loading-card');
    const loadingTitle = document.getElementById('superadmin-loading-title');
    let autoHideTimer = null;
    let isInternalNavigation = false;

    window.showSuperadminLoading = function (title) {
        if (!loadingOverlay || !loadingCard) return;

        if (title && loadingTitle) loadingTitle.textContent = title;

        loadingOverlay.classList.remove('hidden', 'pointer-events-none');
        loadingOverlay.classList.add('flex');

        requestAnimationFrame(() => {
            loadingOverlay.classList.remove('opacity-0');
            loadingOverlay.classList.add('opacity-100');
            loadingCard.classList.remove('scale-95');
            loadingCard.classList.add('scale-100');
        });

        // Safety fallback: auto-hide after 10s if network hangs.
        clearTimeout(autoHideTimer);
        autoHideTimer = setTimeout(() => {
            window.hideSuperadminLoading();
        }, 10000);
    };

    window.hideSuperadminLoading = function () {
        if (!loadingOverlay || !loadingCard) return;

        loadingOverlay.classList.remove('opacity-100');
        loadingOverlay.classList.add('opacity-0');
        loadingCard.classList.remove('scale-100');
        loadingCard.classList.add('scale-95');

        setTimeout(() => {
            loadingOverlay.classList.add('hidden', 'pointer-events-none');
            loadingOverlay.classList.remove('flex');
        }, 200);

        clearTimeout(autoHideTimer);
    };

    function onInitialPageLoad() {
        setTimeout(() => {
            window.hideSuperadminLoading();
        }, 180);
    }

    if (document.readyState === 'complete') {
        onInitialPageLoad();
    } else {
        window.addEventListener('load', onInitialPageLoad);
    }

    // Auto-attach the spinner to every real form submit (opt out with data-no-loading).
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || form.hasAttribute('data-no-loading')) return;

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-wait');
            renderSubmitLoading(submitBtn);
        }
    });

    // Full-page overlay on internal link navigation (sidebar, pagination, detail links…).
    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const href = link.getAttribute('href') || '';
        if (link.target === '_blank' || link.hasAttribute('download') || href === '' || href.startsWith('#') || href.startsWith('javascript:')) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin === window.location.origin && destination.href !== window.location.href) {
            isInternalNavigation = true;
            window.showSuperadminLoading();
        }
    });

    // Reset loading state when restored from bfcache (browser back/forward).
    window.addEventListener('pageshow', function (e) {
        window.hideSuperadminLoading();
        if (e.persisted) {
            document.querySelectorAll('button[type="submit"][disabled]').forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-wait');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    });

    window.addEventListener('beforeunload', () => {
        if (isInternalNavigation) window.showSuperadminLoading();
    });
}

/**
 * Wire every [data-reload-page] button to reload the current page with a spinner,
 * matching resources/js/admin/loading.js::initAdminReloadButtons().
 */
export function initSuperadminReloadButtons() {
    const setLoading = (button, loading) => {
        button.disabled = loading;
        button.setAttribute('aria-busy', loading ? 'true' : 'false');
        button.querySelector('[data-reload-icon]')?.classList.toggle('animate-spin', loading);
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-reload-page]');
        if (!button || button.disabled) return;
        setLoading(button, true);
        window.location.reload();
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-reload-page]').forEach((button) => setLoading(button, false));
    });
}
