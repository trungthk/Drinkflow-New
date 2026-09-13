/**
 * Public Header Interactive Scripts
 * Handles Language switcher dropdown, Mobile nav drawer, and Header scroll blur
 */
export function initPublicHeader() {
    const langBtn = document.getElementById('public-lang-btn');
    const langMenu = document.getElementById('public-lang-menu');
    const container = document.getElementById('public-lang-selector');

    if (langBtn && langMenu) {
        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = !langMenu.classList.contains('hidden');
            if (isOpen) {
                langMenu.classList.add('hidden');
                langBtn.setAttribute('aria-expanded', 'false');
            } else {
                langMenu.classList.remove('hidden');
                langBtn.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(e) {
            if (container && !container.contains(e.target)) {
                langMenu.classList.add('hidden');
                langBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Mobile Nav Drawer Toggle
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const mobileMenu = document.getElementById('mobile-nav-menu');
    const mobileIcon = document.getElementById('mobile-nav-icon');

    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function() {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                if (mobileIcon) mobileIcon.textContent = 'close';
            } else {
                mobileMenu.classList.add('hidden');
                if (mobileIcon) mobileIcon.textContent = 'menu';
            }
        });
    }
}
