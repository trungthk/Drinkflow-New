/**
 * Global User Go To Top Floating Button Module
 */
export function initGlobalGoToTop() {
    const btn = document.getElementById('global-go-to-top-btn');
    const tooltip = document.getElementById('global-go-to-top-tooltip');
    if (!btn) return;

    function handleScroll() {
        if (window.scrollY > 240) {
            btn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-3');
            btn.classList.add('opacity-100', 'translate-y-0');
            if (tooltip) tooltip.classList.remove('hidden');
        } else {
            btn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-3');
            btn.classList.remove('opacity-100', 'translate-y-0');
            if (tooltip) tooltip.classList.add('hidden');
        }
    }

    btn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
}
