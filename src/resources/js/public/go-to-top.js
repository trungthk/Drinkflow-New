/**
 * Public Go To Top Floating Button Module
 */
export function initGoToTop() {
    const goToTopBtn = document.getElementById('go-to-top-btn');
    if (!goToTopBtn) return;

    function handleScroll() {
        if (window.scrollY > 280) {
            goToTopBtn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-3');
            goToTopBtn.classList.add('opacity-100', 'translate-y-0');
        } else {
            goToTopBtn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-3');
            goToTopBtn.classList.remove('opacity-100', 'translate-y-0');
        }
    }

    goToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
}
