{{-- Captcha challenge of the admin auth forms (login, registration); hidden in the local environment. --}}
@if(\App\Support\Helpers\CaptchaHelper::isCaptchaEnabled())
    <div class="rounded-xl bg-surface-container-low border border-outline-variant/60 p-3.5 space-y-2">
        <div class="flex items-center justify-between text-xs font-semibold text-on-surface">
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-primary">shield</span>
                <span>{{ __('admin.captcha_bot_protection') }}</span>
            </div>
            <span class="text-[10px] font-mono text-primary font-bold">{{ __('admin.level_2_active') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5">
                <div id="captcha-img-wrapper"
                     data-captcha-api="{{ url('/captcha/api/contact') }}"
                     data-captcha-fallback="{{ captcha_src('contact') }}"
                     data-loading-text="{{ __('global.feedback.captcha_loading') }}"
                     class="w-[120px] h-[38px] rounded-lg border border-outline-variant bg-surface-container flex items-center justify-center overflow-hidden cursor-pointer shrink-0 shadow-2xs hover:border-primary/50 transition-colors"
                     title="{{ __('global.feedback.captcha_refresh') }}">
                    {!! captcha_img('contact') !!}
                </div>
                <button type="button"
                        id="refresh-captcha-btn"
                        class="w-[38px] h-[38px] rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface-variant hover:text-primary flex items-center justify-center transition-colors cursor-pointer shrink-0 shadow-2xs"
                        title="{{ __('global.feedback.captcha_refresh') }}">
                    <span class="material-symbols-outlined text-[18px] transition-transform duration-300">sync</span>
                </button>
            </div>
            <input class="flex-1 w-full h-[38px] px-3 rounded-lg border @error('captcha') border-red-500 @else border-outline-variant @enderror bg-surface-container-lowest text-on-surface text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline/60 font-mono"
                   id="admin-captcha"
                   name="captcha"
                   type="text"
                   maxlength="6"
                   required
                   placeholder="{{ __('global.feedback.captcha_placeholder') }}">
        </div>
        @error('captcha')
            <p class="text-xs text-red-500 mt-1 flex items-center gap-1 font-medium">
                <span class="material-symbols-outlined text-[14px]">error</span>
                {{ $message }}
            </p>
        @enderror
    </div>
@endif
