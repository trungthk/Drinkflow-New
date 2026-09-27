/**
 * Guide detail pages: clicking an image inside `.guide-article` opens it full screen.
 *
 * The overlay markup comes from <x-guides.image-lightbox />. Close with the X button, Esc or a click
 * on the backdrop; move between the page's images with the prev/next buttons, arrow keys or a swipe.
 */
export function initGuideLightbox() {
    const overlay = document.querySelector('[data-guide-lightbox]');
    const images = [...document.querySelectorAll('.guide-article img')];
    if (!overlay || images.length === 0) return;

    // Escape any stacking context of the page content so the overlay covers the sticky header too.
    document.body.appendChild(overlay);

    const view = overlay.querySelector('[data-guide-lightbox-image]');
    const caption = overlay.querySelector('[data-guide-lightbox-caption]');
    const counter = overlay.querySelector('[data-guide-lightbox-counter]');
    const prevBtn = overlay.querySelector('[data-guide-lightbox-prev]');
    const nextBtn = overlay.querySelector('[data-guide-lightbox-next]');
    const closeBtn = overlay.querySelector('[data-guide-lightbox-close]');

    let i18n = {};
    try {
        i18n = JSON.parse(overlay.dataset.i18n || '{}');
    } catch (error) {
        i18n = {};
    }

    let index = 0;
    let lastFocus = null;
    const multiple = images.length > 1;
    prevBtn.classList.toggle('hidden', !multiple);
    nextBtn.classList.toggle('hidden', !multiple);

    const show = (i) => {
        index = (i + images.length) % images.length;
        const img = images[index];
        view.src = img.currentSrc || img.src;
        view.alt = img.alt || '';
        caption.textContent = img.alt || '';
        counter.textContent = multiple
            ? (i18n.counter || ':current / :total').replace(':current', String(index + 1)).replace(':total', String(images.length))
            : '';
    };

    const open = (i) => {
        lastFocus = document.activeElement;
        show(i);
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
    };

    const close = () => {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.style.overflow = '';
        lastFocus?.focus?.();
    };

    const isOpen = () => !overlay.classList.contains('hidden');

    images.forEach((img, i) => {
        img.classList.add('cursor-zoom-in');
        img.setAttribute('tabindex', '0');
        img.setAttribute('role', 'button');
        img.addEventListener('click', (event) => {
            event.preventDefault();
            open(i);
        });
        img.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                open(i);
            }
        });
    });

    prevBtn.addEventListener('click', () => show(index - 1));
    nextBtn.addEventListener('click', () => show(index + 1));
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) close();
    });

    document.addEventListener('keydown', (event) => {
        if (!isOpen()) return;
        if (event.key === 'Escape') close();
        else if (event.key === 'ArrowLeft' && multiple) show(index - 1);
        else if (event.key === 'ArrowRight' && multiple) show(index + 1);
    });

    let touchStartX = null;
    overlay.addEventListener('touchstart', (event) => {
        touchStartX = event.touches[0]?.clientX ?? null;
    }, { passive: true });
    overlay.addEventListener('touchend', (event) => {
        if (touchStartX === null || !multiple) return;
        const deltaX = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
        touchStartX = null;
        if (Math.abs(deltaX) > 50) show(index + (deltaX < 0 ? 1 : -1));
    }, { passive: true });
}
