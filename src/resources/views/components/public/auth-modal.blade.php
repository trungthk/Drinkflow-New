@props([
    'googleAuthUrl' => null,
    'termsUrl' => null,
])

@php
    $hasLoginError = session()->has('login_error') || (isset($errors) && $errors->has('email'));
    $loginErrorMessage = session('login_error') ?? (isset($errors) ? $errors->first('email') : null);
    $hasAuthNotice = session()->has('auth_notice');
    $authNoticeMessage = session('auth_notice');
@endphp

<!-- GOOGLE WORKSPACE SSO LOGIN / SIGN-UP MODAL -->
<div id="auth-modal"
     data-auto-open="{{ ($hasLoginError || $hasAuthNotice) ? 'true' : 'false' }}"
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

        <!-- Brand Icon / Security Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-[#006948] border border-[#005137] ring-1 ring-emerald-500/20 flex items-center justify-center text-white shadow-sm flex-shrink-0">
                <span class="material-symbols-outlined text-[22px]">local_cafe</span>
            </div>
            <div>
                <h3 id="modal-title" class="text-xl font-bold text-[#0F172A] tracking-tight">
                    {{ __('public.auth_modal.title') }}
                </h3>
                <p class="text-xs text-[#64748B]">
                    {{ __('public.auth_modal.subtitle') }}
                </p>
            </div>
        </div>

        @if($hasAuthNotice && $authNoticeMessage)
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-2.5">
                <span class="material-symbols-outlined text-[18px] text-emerald-600 flex-shrink-0 mt-0.5">meeting_room</span>
                <span class="leading-relaxed font-medium">
                    {{ $authNoticeMessage }}
                </span>
            </div>
        @endif

        @if($hasLoginError && $loginErrorMessage)
            <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200/80 text-red-700 text-xs flex items-start gap-2.5 animate-shake">
                <span class="material-symbols-outlined text-[18px] text-red-600 flex-shrink-0 mt-0.5">error</span>
                <span class="leading-relaxed font-medium">
                    {{ $loginErrorMessage }}
                </span>
            </div>
        @endif

        <!-- Instructions -->
        <div class="bg-[#eff4ff] border border-emerald-100 rounded-xl p-3.5 mb-6 text-xs text-[#3d4a42] leading-relaxed">
            <div class="font-semibold text-[#006948] mb-1 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">verified_user</span>
                {{ __('public.auth_modal.instruction_title') }}
            </div>
            <p>{{ __('public.auth_modal.instruction_desc') }}</p>
        </div>

        <!-- Google Workspace SSO Action -->
        <div class="space-y-4">
            <a href="{{ $googleAuthUrl }}"
               class="google-sso-link w-full h-12 rounded-xl bg-[#006948] hover:bg-[#005137] text-white font-semibold text-sm flex items-center justify-center gap-3 shadow-md hover:shadow-lg transition-all active:scale-[0.99]">
                <!-- Official Multi-color Google SVG Icon -->
                <svg class="w-5 h-5 bg-white rounded-full p-0.5 flex-shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>{{ __('public.auth_modal.btn_google') }}</span>
            </a>

            <div class="text-[12px] leading-relaxed text-[#475569]">
                {{ __('public.auth_modal.terms_prefix') }} <a href="{{ $termsUrl }}" class="text-[#006948] hover:underline font-medium">{{ __('public.auth_modal.terms_link') }}</a> {{ __('public.auth_modal.terms_suffix') }}
            </div>
        </div>

    </div>
</div>
