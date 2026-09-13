/**
 * Public Video Intro Modal Interactive Module
 */
export function initVideoModal() {
    const videoModal = document.getElementById('video-modal');
    const videoCard = document.getElementById('video-modal-card');
    const closeBtn = document.getElementById('close-video-modal-btn');
    const videoPlayer = document.getElementById('about-drinkflow-player');
    const triggerBtns = document.querySelectorAll('.open-video-btn, [data-open-video-modal]');

    if (!videoModal || !videoCard) return;

    window.openVideoModal = function(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        videoModal.classList.remove('hidden', 'pointer-events-none');
        videoModal.classList.add('flex');
        requestAnimationFrame(() => {
            videoModal.classList.remove('opacity-0');
            videoCard.classList.remove('scale-95');
            videoCard.classList.add('scale-100');
        });
        document.body.style.overflow = 'hidden';
        if (videoPlayer) {
            videoPlayer.currentTime = 0;
            videoPlayer.play().catch(() => {});
        }
    };

    window.closeVideoModal = function() {
        videoModal.classList.add('opacity-0');
        videoCard.classList.remove('scale-100');
        videoCard.classList.add('scale-95');
        if (videoPlayer) {
            videoPlayer.pause();
        }
        setTimeout(() => {
            videoModal.classList.add('hidden', 'pointer-events-none');
            videoModal.classList.remove('flex');
            document.body.style.overflow = '';
        }, 200);
    };

    triggerBtns.forEach(btn => {
        btn.addEventListener('click', window.openVideoModal);
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', window.closeVideoModal);
    }

    videoModal.addEventListener('click', (e) => {
        if (e.target === videoModal) {
            window.closeVideoModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !videoModal.classList.contains('hidden')) {
            window.closeVideoModal();
        }
    });
}
