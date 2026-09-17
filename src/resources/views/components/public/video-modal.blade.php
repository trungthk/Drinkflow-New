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
                aria-label="{{ __('public.video_modal.close_label') }}"
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
