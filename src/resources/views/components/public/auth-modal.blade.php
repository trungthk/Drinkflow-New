@props([
    'googleAuthUrl' => route('auth.google'),
    'termsUrl' => url('/terms'),
])

<!-- GOOGLE WORKSPACE SSO LOGIN / SIGN-UP MODAL -->
<div id="auth-modal"
     aria-labelledby="modal-title"
     aria-modal="true"
     role="dialog"
     class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 transition-opacity duration-200 opacity-0 pointer-events-none">
    <div id="modal-card" class="w-full max-w-[480px] bg-white rounded-2xl border border-[#E2E8F0] shadow-2xl p-6 sm:p-8 relative transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button id="close-modal-btn"
                type="button"
                aria-label="{{ __('public.auth_modal.close') }}"
                class="absolute top-5 right-5 text-[#64748B] hover:text-[#0F172A] hover:bg-slate-100 p-1.5 rounded-lg transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <!-- Modal Header -->
        <div class="flex flex-col items-center text-center">
            <div class="w-12 h-12 rounded-xl bg-[#006948] flex items-center justify-center text-white shadow-sm mb-4">
                <span class="material-symbols-outlined text-[28px]">local_cafe</span>
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#ECFDF5] text-[#065F46] text-xs mb-2 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span>
                {{ __('public.auth_modal.badge') }}
            </div>
            <h3 id="modal-title" class="text-xl font-bold text-[#0F172A]">
                {{ __('public.auth_modal.title') }}
            </h3>
            <p class="text-xs sm:text-sm text-[#475569] mt-2 max-w-sm leading-relaxed">
                {{ __('public.auth_modal.desc') }}
            </p>
        </div>

        <!-- Error Alert if Login Failed -->
        @if(session('login_error') || $errors->has('email'))
            <div class="mt-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5 text-left shadow-2xs animate-fadeIn">
                <span class="material-symbols-outlined text-rose-600 text-[19px] shrink-0 mt-0.5">error</span>
                <div class="flex-1">
                    <p class="font-bold text-rose-900">{{ __('public.auth_modal.login_failed') }}</p>
                    <p class="mt-0.5 leading-relaxed text-rose-700">{{ session('login_error') ?: $errors->first('email') }}</p>
                </div>
            </div>
        @endif

        <!-- SSO Action Button -->
        <div class="mt-6 space-y-3">
            <a href="{{ $googleAuthUrl }}"
               class="btn-google-sso-direct w-full flex items-center justify-center gap-3 bg-white border border-[#CBD5E1] hover:bg-[#F8FAFC] hover:border-[#94A3B8] text-[#0F172A] font-semibold py-3 px-4 rounded-xl shadow-2xs transition-all duration-150 active:scale-[0.99] group text-sm">
                <!-- Google 'G' SVG Logo -->
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"></path>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"></path>
                </svg>
                <span>{{ __('public.auth_modal.btn_google') }}</span>
            </a>
            <p class="text-center text-[12px] text-[#545c72]">
                {{ __('public.auth_modal.security_notice') }}
            </p>
        </div>

        <!-- Security Information Callout Box -->
        <div class="mt-6 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl p-3.5 flex items-start gap-3 text-left">
            <span class="material-symbols-outlined text-[#006948] text-[20px] flex-shrink-0 mt-0.5">shield</span>
            <div class="text-[12px] leading-relaxed text-[#475569]">
                {{ __('public.auth_modal.terms_prefix') }} <a href="{{ $termsUrl }}" class="text-[#006948] hover:underline font-medium">{{ __('public.auth_modal.terms_link') }}</a> {{ __('public.auth_modal.terms_suffix') }}
            </div>
        </div>

    </div>
</div>

<!-- Modal Interactive JavaScript -->
<script>
    (function() {
        const modal = document.getElementById('auth-modal');
        const modalCard = document.getElementById('modal-card');
        const closeBtn = document.getElementById('close-modal-btn');
        const triggerButtons = document.querySelectorAll('.btn-google-sso');
        const isAuthenticated = @json(auth('web')->check());
        const meUrl = @json(route('user.me.dashboard'));

        function openModal(e) {
            if (e) e.preventDefault();
            if (isAuthenticated) {
                window.location.href = meUrl;
                return;
            }
            modal.classList.remove('hidden');
            modal.classList.remove('pointer-events-none');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                modalCard.classList.remove('scale-95');
                modalCard.classList.add('scale-100');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('opacity-0');
            modalCard.classList.remove('scale-100');
            modalCard.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.classList.add('pointer-events-none');
                document.body.style.overflow = '';
            }, 200);
        }

        const hasLoginError = @json(session()->has('login_error') || $errors->has('email'));
        if (hasLoginError) {
            openModal();
        }

        triggerButtons.forEach(btn => {
            btn.addEventListener('click', openModal);
        });

        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
