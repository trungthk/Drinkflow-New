<!-- GO TO TOP BUTTON -->
<button id="go-to-top-btn"
        type="button"
        aria-label="{{ __('public.go_to_top') }}"
        title="{{ __('public.go_to_top') }}"
        class="fixed bottom-6 right-6 z-40 w-11 h-11 rounded-full bg-[#006948] hover:bg-[#047857] text-white shadow-lg flex items-center justify-center cursor-pointer transition-all duration-300 opacity-0 pointer-events-none translate-y-3 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#006948] focus:ring-offset-2">
    <span class="material-symbols-outlined text-[20px]">arrow_upward</span>
</button>

<script>
    (function() {
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
    })();
</script>
