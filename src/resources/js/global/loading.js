/**
 * Global User Loading & Submit Overlay Module
 */
export function initGlobalLoading() {
    const loadingOverlay = document.getElementById('global-page-loading');
    const loadingCard = document.getElementById('global-loading-card');
    const loadingTitle = document.getElementById('global-loading-title');
    let autoHideTimer = null;

    window.showGlobalLoading = function(title) {
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

        // Safety fallback: auto-hide after 10s if network hangs
        clearTimeout(autoHideTimer);
        autoHideTimer = setTimeout(() => {
            window.hideGlobalLoading();
        }, 10000);
    };

    window.hideGlobalLoading = function() {
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

    // Smooth subtle reveal on page readiness
    function onInitialPageLoad() {
        setTimeout(() => {
            window.hideGlobalLoading();
        }, 180);
    }

    if (document.readyState === 'complete') {
        onInitialPageLoad();
    } else {
        window.addEventListener('load', onInitialPageLoad);
    }

    // Auto-attach to all forms on submit
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.hasAttribute('data-no-loading')) return;

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        let customTitle = loadingTitle?.dataset.submitTitle || '';
        if (submitBtn) {
            if (submitBtn.hasAttribute('data-loading-title')) {
                customTitle = submitBtn.getAttribute('data-loading-title');
            }
        }

        if (submitBtn && !submitBtn.disabled) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-wait');
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>';
        }
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const href = link.getAttribute('href') || '';
        if (link.target === '_blank' || link.hasAttribute('download') || href === '' || href.startsWith('#') || href.startsWith('javascript:')) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin === window.location.origin && destination.href !== window.location.href) {
            window.showGlobalLoading();
        }
    });

    // Reset loading on back button (browser bfcache)
    window.addEventListener('pageshow', function(e) {
        window.hideGlobalLoading();
        if (e.persisted) {
            document.querySelectorAll('button[type="submit"][disabled]').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-wait');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    });

    window.addEventListener('beforeunload', () => window.showGlobalLoading());
}
