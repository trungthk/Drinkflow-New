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
