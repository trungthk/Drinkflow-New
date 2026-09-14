/**
 * Public Loading & Submit Overlay Interactive Module
 */
export function initPublicLoading() {
    const overlay = document.getElementById('public-page-loading');
    const card = document.getElementById('public-loading-card');
    const title = document.getElementById('public-loading-title');
    let autoHideTimer = null;

    const defaultTitle = title?.dataset.defaultTitle || '';
    const connectingGoogleTitle = title?.dataset.googleTitle || defaultTitle;
    const submittingTitle = title?.dataset.submitTitle || defaultTitle;

    window.showPublicLoading = function(t) {
        if (!overlay || !card) return;

        if (title) title.textContent = t || defaultTitle;

        overlay.classList.remove('hidden', 'pointer-events-none');
        overlay.classList.add('flex');

        requestAnimationFrame(() => {
            overlay.classList.remove('opacity-0');
            overlay.classList.add('opacity-100');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        });

        clearTimeout(autoHideTimer);
        autoHideTimer = setTimeout(() => {
            window.hidePublicLoading();
        }, 10000);
    };

    window.hidePublicLoading = function() {
        if (!overlay || !card) return;

        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');

        setTimeout(() => {
            overlay.classList.add('hidden', 'pointer-events-none');
            overlay.classList.remove('flex');
        }, 200);

        clearTimeout(autoHideTimer);
    };

    // Smooth subtle reveal on page readiness
    function onInitialLoad() {
        setTimeout(() => {
            window.hidePublicLoading();
        }, 180);
    }

    if (document.readyState === 'complete') {
        onInitialLoad();
    } else {
        window.addEventListener('load', onInitialLoad);
    }

    // Auto-attach to all forms on submit
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.hasAttribute('data-no-loading')) return;

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        let customTitle = submittingTitle;
        if (submitBtn) {
            if (submitBtn.hasAttribute('data-loading-title')) {
                customTitle = submitBtn.getAttribute('data-loading-title');
            }
        }

        window.showPublicLoading(customTitle);

        if (submitBtn && !submitBtn.disabled) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-wait');
        }
    });

    // Handle Google SSO clicks
    document.addEventListener('click', function(e) {
        const ssoLink = e.target.closest('a[href*="auth/google"], .google-sso-link, .btn-google-sso-direct');
        if (ssoLink) {
            window.showPublicLoading(connectingGoogleTitle);
        }
    });

    // Reset loading on bfcache
    window.addEventListener('pageshow', function() {
            window.hidePublicLoading();
            document.querySelectorAll('button[disabled], a.pointer-events-none').forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-wait', 'pointer-events-none');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
    });

    window.addEventListener('beforeunload', () => window.showPublicLoading(defaultTitle));
}
