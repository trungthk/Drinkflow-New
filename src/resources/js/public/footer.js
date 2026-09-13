/**
 * Public Footer Interactive Dropdown Module
 */
export function initFooterNav() {
    const footerBtn = document.getElementById('footer-nav-btn');
    const footerMenu = document.getElementById('footer-nav-menu');
    const footerArrow = document.getElementById('footer-nav-arrow');
    const container = document.getElementById('footer-nav-dropdown');

    if (footerBtn && footerMenu) {
        footerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = !footerMenu.classList.contains('hidden');
            if (isOpen) {
                footerMenu.classList.add('hidden');
                footerBtn.setAttribute('aria-expanded', 'false');
                if (footerArrow) footerArrow.classList.remove('rotate-180');
            } else {
                footerMenu.classList.remove('hidden');
                footerBtn.setAttribute('aria-expanded', 'true');
                if (footerArrow) footerArrow.classList.add('rotate-180');
            }
        });

        document.addEventListener('click', function(e) {
            if (container && !container.contains(e.target)) {
                footerMenu.classList.add('hidden');
                footerBtn.setAttribute('aria-expanded', 'false');
                if (footerArrow) footerArrow.classList.remove('rotate-180');
            }
        });
    }
}
