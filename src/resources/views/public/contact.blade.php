<x-public.layout
    :title="__('contact.meta.title')"
    :description="__('contact.meta.description')"
    :ogTitle="__('contact.meta.title')"
    :ogDescription="__('contact.meta.description')"
    ogType="website"
    activeTab="contact"
    :version="$appVersion ?? 'v2.3.0'"
    :termsUrl="$termsUrl ?? url('/terms')"
    :versionsUrl="$versionsUrl ?? url('/versions')"
    :contactUrl="$contactUrl ?? route('contact')"
    :googleAuthUrl="$googleAuthUrl ?? route('auth.google')"
>
    <!-- Hero Intro Section -->
    <section class="bg-white border-b border-slate-200/80 py-10 px-6">
        <div class="max-w-[1200px] mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-[#006948] border border-emerald-200/60 mb-4">
                <span class="w-2 h-2 rounded-full bg-[#006948] animate-pulse"></span>
                <span class="text-xs font-semibold tracking-wider uppercase">{{ __('contact.hero.badge') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#0b1c30] mb-3 tracking-tight">
                {{ __('contact.hero.title') }}
            </h1>
            <p class="text-sm sm:text-base text-slate-600 max-w-3xl leading-relaxed">
                {{ __('contact.hero.subtitle') }}
            </p>
        </div>
    </section>

    <!-- Main Content Grid -->
    <main class="max-w-[1200px] w-full mx-auto px-6 py-10 flex-1">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column (7 cols): Online Contact & Ticket Submission Form -->
            <div class="lg:col-span-7 bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-sm">
                <div class="border-b border-slate-100 pb-5 mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-[#0b1c30]">{{ __('contact.form.title') }}</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        {{ __('contact.form.subtitle') }}
                    </p>
                </div>

                @if($errors->has('rate_limit'))
                    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[20px] text-amber-600">warning</span>
                        <span class="font-medium">{{ $errors->first('rate_limit') }}</span>
                    </div>
                @elseif($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        <div class="flex items-center gap-2 font-semibold mb-1">
                            <span class="material-symbols-outlined text-[18px]">error</span>
                            <span>{{ __('contact.form.validation_error_title') }}</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-xs text-red-600 mt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="space-y-5" id="contactForm" method="POST" action="{{ route('contact.store') }}">
                    @csrf

                    <!-- Full Name & Work Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="full_name">
                                {{ __('contact.form.full_name') }} <span class="text-[#ba1a1a]">*</span>
                            </label>
                            <input class="w-full h-[38px] px-3 rounded-lg border @error('full_name') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                                   id="full_name"
                                   name="full_name"
                                   value="{{ old('full_name', auth('web')->user()?->name ?? '') }}"
                                   placeholder="{{ __('contact.form.full_name_placeholder') }}"
                                   required
                                   type="text">
                            @error('full_name')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="work_email">
                                {{ __('contact.form.work_email') }} <span class="text-[#ba1a1a]">*</span>
                            </label>
                            <input class="w-full h-[38px] px-3 rounded-lg border @error('work_email') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                                   id="work_email"
                                   name="work_email"
                                   value="{{ old('work_email', auth('web')->user()?->email ?? '') }}"
                                   placeholder="{{ __('contact.form.work_email_placeholder') }}"
                                   required
                                   type="email">
                            @error('work_email')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Phone & Department -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="phone">
                                {{ __('contact.form.phone') }} <span class="text-[#ba1a1a]">*</span>
                            </label>
                            <input class="w-full h-[38px] px-3 rounded-lg border @error('phone') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   placeholder="{{ __('contact.form.phone_placeholder') }}"
                                   required
                                   type="tel">
                            @error('phone')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="company">
                                {{ __('contact.form.company') }} <span class="text-[#ba1a1a]">*</span>
                            </label>
                            <input class="w-full h-[38px] px-3 rounded-lg border @error('company') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                                   id="company"
                                   name="company"
                                   value="{{ old('company') }}"
                                   placeholder="{{ __('contact.form.company_placeholder') }}"
                                   required
                                   type="text">
                            @error('company')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Subject Dropdown -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="topic">
                            {{ __('contact.form.topic') }} <span class="text-[#ba1a1a]">*</span>
                        </label>
                        <select class="w-full h-[38px] px-3 rounded-lg border @error('topic') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all"
                                id="topic"
                                name="topic"
                                required>
                            <option disabled {{ old('topic') ? '' : 'selected' }} value="">{{ __('contact.form.topic_select') }}</option>
                            <option value="vietqr" {{ old('topic') === 'vietqr' ? 'selected' : '' }}>{{ __('contact.form.topics.vietqr') }}</option>
                            <option value="deploy" {{ old('topic') === 'deploy' ? 'selected' : '' }}>{{ __('contact.form.topics.deploy') }}</option>
                            <option value="feedback" {{ old('topic') === 'feedback' ? 'selected' : '' }}>{{ __('contact.form.topics.feedback') }}</option>
                            <option value="merchant" {{ old('topic') === 'merchant' ? 'selected' : '' }}>{{ __('contact.form.topics.merchant') }}</option>
                            <option value="other" {{ old('topic') === 'other' ? 'selected' : '' }}>{{ __('contact.form.topics.other') }}</option>
                        </select>
                        @error('topic')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Message Textarea -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="message">
                            {{ __('contact.form.message') }} <span class="text-[#ba1a1a]">*</span>
                        </label>
                        <textarea class="w-full p-3 rounded-lg border @error('message') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                                  id="message"
                                  name="message"
                                  placeholder="{{ __('contact.form.message_placeholder') }}"
                                  required
                                  rows="4">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Anti-Spam Captcha Section (mews/captcha with graceful GD check) -->
                    @if(extension_loaded('gd') && function_exists('gd_info'))
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="captcha">
                                {{ __('contact.form.captcha') }} <span class="text-[#ba1a1a]">*</span>
                            </label>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <div id="captcha-img-wrapper"
                                         data-captcha-api="{{ url('/captcha/api/contact') }}"
                                         data-captcha-fallback="{{ captcha_src('contact') }}"
                                         data-loading-text="{{ __('contact.form.captcha_loading') }}"
                                         class="w-[120px] h-[38px] flex-shrink-0 cursor-pointer select-none rounded-lg border border-slate-300 overflow-hidden shadow-xs hover:border-[#006948] transition-colors bg-white flex items-center justify-center"
                                         title="{{ __('contact.form.captcha_refresh') }}">
                                        {!! captcha_img('contact') !!}
                                    </div>
                                    <button type="button"
                                            id="refresh-captcha-btn"
                                            class="p-2 text-slate-500 hover:text-[#006948] hover:bg-emerald-50 rounded-lg border border-slate-300 transition-all flex-shrink-0 cursor-pointer active:scale-95"
                                            title="{{ __('contact.form.captcha_refresh') }}">
                                        <span class="material-symbols-outlined text-[20px] transition-transform duration-300">refresh</span>
                                    </button>
                                </div>
                                <input class="flex-1 w-full h-[38px] px-3 rounded-lg border @error('captcha') border-red-500 @else border-slate-300 @enderror bg-white text-[#0b1c30] text-sm focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                                       id="captcha"
                                       name="captcha"
                                       placeholder="{{ __('contact.form.captcha_placeholder') }}"
                                       required
                                       autocomplete="off"
                                       type="text">
                            </div>
                            @error('captcha')
                                <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Submit CTA & SLA Commitment -->
                    <div class="pt-2">
                        <button class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-[#006948] hover:bg-[#00855d] text-white text-sm font-semibold shadow-sm transition-all duration-150 active:scale-[0.98] cursor-pointer"
                                type="submit">
                            <span class="material-symbols-outlined text-[18px]">send</span>
                            <span>{{ __('contact.form.submit') }}</span>
                        </button>
                        <div class="flex items-center gap-2 mt-3 text-xs text-slate-500">
                            <span class="material-symbols-outlined text-[16px] text-[#006948]">verified_user</span>
                            <span>{{ __('contact.form.sla_commitment') }}</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Column (5 cols): Direct Contacts & FAQ Mini -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Direct Tech & Ops Desk Card -->
                <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#006948] flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[22px]">support_agent</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-[#0b1c30]">{{ __('contact.direct.title') }}</h3>
                            <p class="text-xs text-slate-400 font-medium">{{ __('contact.direct.subtitle') }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 space-y-4 text-xs sm:text-sm">
                        <!-- Hotline -->
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-[18px]">call</span>
                            <div>
                                <p class="font-medium text-slate-700">{{ __('contact.direct.hotline_label') }}</p>
                                <a class="mt-0.5 inline-block text-base font-bold text-[#006948] hover:underline" href="tel:0377300950">{{ __('contact.direct.hotline_value') }}</a>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-[18px]">mail</span>
                            <div>
                                <p class="font-medium text-slate-700">{{ __('contact.direct.email_label') }}</p>
                                <a class="mt-0.5 inline-block font-medium text-[#006948] hover:underline" href="mailto:drinkflowsupport@gmail.com">{{ __('contact.direct.email_value') }}</a>
                            </div>
                        </div>

                        <!-- Hours -->
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-[18px]">schedule</span>
                            <div>
                                <p class="font-medium text-slate-700">{{ __('contact.direct.hours_label') }}</p>
                                <p class="text-slate-600 mt-0.5">{{ __('contact.direct.hours_value') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Mini Card (Interactive Accordion) -->
                <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#006948] flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[22px]">quiz</span>
                        </div>
                        <h3 class="text-base font-bold text-[#0b1c30]">{{ __('contact.faq.title') }}</h3>
                    </div>

                    <div class="mt-4 space-y-3" id="faq-accordion">
                        @foreach(['q1' => 'a1', 'q2' => 'a2', 'q3' => 'a3', 'q4' => 'a4'] as $qKey => $aKey)
                        <div class="border border-slate-200/80 rounded-xl overflow-hidden bg-slate-50/50 hover:bg-slate-50 transition-colors">
                            <button type="button" class="faq-toggle w-full p-3.5 text-left text-xs sm:text-sm font-semibold text-[#0b1c30] flex items-center justify-between gap-2 cursor-pointer">
                                <span>{{ __('contact.faq.' . $qKey) }}</span>
                                <span class="material-symbols-outlined text-[18px] text-slate-400 transition-transform duration-200">expand_more</span>
                            </button>
                            <div class="faq-content hidden px-3.5 pb-3.5 pt-1 text-xs sm:text-sm text-slate-600 border-t border-slate-100 leading-relaxed">
                                {{ __('contact.faq.' . $aKey) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Success Notification Modal / Dialog -->
    <div class="fixed inset-0 z-50 @if(session('success_ticket')) flex @else hidden @endif items-center justify-center bg-black/40 backdrop-blur-xs p-4 transition-all" id="successModal">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl max-w-md w-full p-6 text-center animate-in fade-in zoom-in-95 duration-200">
            <div class="w-14 h-14 rounded-full bg-emerald-50 text-[#006948] mx-auto flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[32px]">check_circle</span>
            </div>
            <h3 class="text-xl font-bold text-[#0b1c30] mb-2">{{ __('contact.modal.title') }}</h3>
            <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                {{ __('contact.success_modal.message') }}
                <span class="font-bold text-[#006948] tracking-wider" id="modalTicketCode">{{ session('success_ticket', '#DF-00000') }}</span>
            </p>
            <button class="w-full py-2.5 px-4 rounded-xl bg-[#006948] hover:bg-[#00855d] text-white font-semibold text-sm transition-colors cursor-pointer"
                    id="closeSuccessModalBtn"
                    type="button">
                {{ __('contact.modal.btn_close') }}
            </button>
        </div>
    </div>
</x-public.layout>
