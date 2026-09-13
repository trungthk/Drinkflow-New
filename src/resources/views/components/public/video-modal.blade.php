<!-- VIDEO MODAL: GIỚI THIỆU VỀ DRINKFLOW -->
<div id="video-modal"
     aria-labelledby="video-modal-title"
     aria-modal="true"
     role="dialog"
     class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm hidden items-center justify-center p-4 transition-opacity duration-200 opacity-0 pointer-events-none">
    <div id="video-modal-card" class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-2xl p-4 sm:p-6 relative transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button id="close-video-modal-btn"
                type="button"
                aria-label="Đóng video"
                class="absolute top-4 right-4 z-10 text-slate-400 hover:text-slate-800 hover:bg-slate-100 p-2 rounded-xl transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[24px]">close</span>
        </button>

        <!-- Modal Header -->
        <div class="flex items-center gap-3 mb-4 pr-10">
            <div class="w-10 h-10 rounded-xl bg-[#006948] flex items-center justify-center text-white shadow-sm flex-shrink-0">
                <span class="material-symbols-outlined text-[22px]">play_circle</span>
            </div>
            <div>
                <h3 id="video-modal-title" class="text-lg sm:text-xl font-bold text-[#0F172A]">
                    {{ __('public.video_modal.title') }}
                </h3>
                <p class="text-xs text-[#545c72]">
                    {{ __('public.video_modal.note') }}
                </p>
            </div>
        </div>

        <!-- Video Player -->
        <div class="relative overflow-hidden rounded-xl bg-black aspect-video flex items-center justify-center shadow-inner">
            <video id="about-drinkflow-player"
                   class="w-full h-full object-contain"
                   controls
                   playsinline
                   preload="metadata">
                <source src="{{ asset('files/about-drinkflow.mp4') }}" type="video/mp4">
                {{ __('public.video_modal.yt_title') }}
            </video>
        </div>
    </div>
</div>

<!-- Video Modal Interactive JavaScript -->
<script>
    (function() {
        const videoModal = document.getElementById('video-modal');
        const videoCard = document.getElementById('video-modal-card');
        const closeBtn = document.getElementById('close-video-modal-btn');
        const videoPlayer = document.getElementById('about-drinkflow-player');
        const triggerBtns = document.querySelectorAll('.open-video-btn');

        function openVideoModal(e) {
            if (e) e.preventDefault();
            videoModal.classList.remove('hidden');
            videoModal.classList.remove('pointer-events-none');
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
        }

        function closeVideoModal() {
            videoModal.classList.add('opacity-0');
            videoCard.classList.remove('scale-100');
            videoCard.classList.add('scale-95');
            if (videoPlayer) {
                videoPlayer.pause();
            }
            setTimeout(() => {
                videoModal.classList.add('hidden');
                videoModal.classList.remove('flex');
                videoModal.classList.add('pointer-events-none');
                document.body.style.overflow = '';
            }, 200);
        }

        triggerBtns.forEach(btn => {
            btn.addEventListener('click', openVideoModal);
        });

        if (closeBtn) closeBtn.addEventListener('click', closeVideoModal);

        videoModal.addEventListener('click', (e) => {
            if (e.target === videoModal) {
                closeVideoModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !videoModal.classList.contains('hidden')) {
                closeVideoModal();
            }
        });
    })();
</script>
