/**
 * Public Terms Page Interactive Module (Table of Contents Scrollspy)
 */
export function initTermsPage() {
    const sections = document.querySelectorAll('article[id]');
    const navLinks = document.querySelectorAll('#toc-nav a');

    if (sections.length === 0 || navLinks.length === 0) return;

    function changeActiveToc() {
        let index = sections.length;

        while (--index && window.scrollY + 140 < sections[index].offsetTop) {}

        navLinks.forEach((link) => {
            link.classList.remove('text-[#006948]', 'bg-[#eff4ff]', 'font-semibold', 'border-l-2', 'border-[#006948]');
            link.classList.add('text-[#545c72]', 'hover:text-[#0F172A]');
            const arrow = link.querySelector('.material-symbols-outlined');
            if (arrow) {
                arrow.textContent = 'chevron_right';
                arrow.classList.add('opacity-40');
                arrow.classList.remove('opacity-70');
            }
        });

        if (navLinks[index]) {
            navLinks[index].classList.remove('text-[#545c72]', 'hover:text-[#0F172A]');
            navLinks[index].classList.add('text-[#006948]', 'bg-[#eff4ff]', 'font-semibold', 'border-l-2', 'border-[#006948]');
            const activeArrow = navLinks[index].querySelector('.material-symbols-outlined');
            if (activeArrow) {
                activeArrow.textContent = 'arrow_forward';
                activeArrow.classList.remove('opacity-40');
                activeArrow.classList.add('opacity-70');
            }
        }
    }

    changeActiveToc();
    window.addEventListener('scroll', changeActiveToc, { passive: true });
}
