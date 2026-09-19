<x-global.layout 
    :title="'DrinkFlow - ' . __('global.feedback.page_title')" 
    :user="$user"
    activeTab="feedback" 
    :breadcrumbs="$breadcrumbs"
    :unreadNotificationsCount="$unreadNotificationsCount ?? 0"
    :notifications="$notifications ?? collect()"
>
<div 
    x-data="{
        rating: 5,
        subsystem: 'all',
        content: '',
        get charCount() {
            return this.content.length;
        },
        get sentimentText() {
            const map = {
                1: '{{ __('global.feedback.sentiment_1') }}',
                2: '{{ __('global.feedback.sentiment_2') }}',
                3: '{{ __('global.feedback.sentiment_3') }}',
                4: '{{ __('global.feedback.sentiment_4') }}',
                5: '{{ __('global.feedback.sentiment_5') }}'
            };
            return map[this.rating] || '';
        },
        resetForm() {
            this.rating = 5;
            this.subsystem = 'all';
            this.content = '';
        }
    }" 
    class="space-y-6"
>
    <!-- Status & Error Flash Notifications -->
    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-2xs">
            <div class="flex items-center gap-2 font-semibold mb-1">
                <span class="material-symbols-outlined text-rose-600 text-[20px]">error</span>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <!-- Page Header & Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('global.feedback.title') }}</h1>
            <p class="text-sm text-slate-600 mt-1">
                {{ __('global.feedback.subtitle') }}
            </p>
        </div>
    </div>

    <!-- Two-Section Layout: Form (Col 5) & Analytics/Feed (Col 7) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Section 1: Biểu mẫu gửi góp ý (Submit Feedback Form Card) -->
        <section class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 shadow-2xs space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006948]">rate_review</span>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('global.feedback.form_title') }}</h2>
                </div>
                <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ __('global.feedback.internal_badge') }}</span>
            </div>

            <form action="{{ route('user.me.feedback.store') }}" method="POST" class="space-y-5" @reset="resetForm()">
                @csrf

                <!-- Rating selector with interactive stars & sentiment label -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">{{ __('global.feedback.rating_label') }}</label>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex flex-col items-center justify-center gap-2">
                        <div class="flex items-center gap-1.5">
                            <input type="hidden" name="rating" :value="rating">
                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                <button 
                                    type="button" 
                                    @click="rating = star"
                                    class="p-1 transition-transform active:scale-95 focus:outline-none cursor-pointer"
                                    :class="star <= rating ? 'text-[#006948]' : 'text-slate-300'"
                                >
                                    <span 
                                        class="material-symbols-outlined text-[32px]" 
                                        :style="star <= rating ? 'font-variation-settings: \'FILL\' 1;' : 'font-variation-settings: \'FILL\' 0;'"
                                    >star</span>
                                </button>
                            </template>
                        </div>
                        <span class="text-xs font-semibold text-[#006948]" x-text="sentimentText"></span>
                    </div>
                </div>

                <!-- Phân hệ liên quan (Tag / Radio Selector) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">{{ __('global.feedback.subsystem_label') }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="subsystem" value="all" x-model="subsystem" class="peer sr-only">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium border border-slate-200 bg-white peer-checked:bg-[#006948] peer-checked:text-white peer-checked:border-[#006948] transition-all">
                                {{ __('global.feedback.subsystem_all') }}
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="subsystem" value="room" x-model="subsystem" class="peer sr-only">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium border border-slate-200 bg-white peer-checked:bg-[#006948] peer-checked:text-white peer-checked:border-[#006948] transition-all">
                                {{ __('global.feedback.subsystem_room') }}
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="subsystem" value="split_qr" x-model="subsystem" class="peer sr-only">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium border border-slate-200 bg-white peer-checked:bg-[#006948] peer-checked:text-white peer-checked:border-[#006948] transition-all">
                                {{ __('global.feedback.subsystem_split_qr') }}
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="subsystem" value="socket" x-model="subsystem" class="peer sr-only">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium border border-slate-200 bg-white peer-checked:bg-[#006948] peer-checked:text-white peer-checked:border-[#006948] transition-all">
                                {{ __('global.feedback.subsystem_socket') }}
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="subsystem" value="sponsor" x-model="subsystem" class="peer sr-only">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium border border-slate-200 bg-white peer-checked:bg-[#006948] peer-checked:text-white peer-checked:border-[#006948] transition-all">
                                {{ __('global.feedback.subsystem_sponsor') }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Textarea input with live character counter -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="text-xs font-semibold text-slate-700" for="feedbackContent">{{ __('global.feedback.content_label') }}</label>
                        <span class="text-[11px] text-slate-400" x-text="`${charCount} / 1000`"></span>
                    </div>
                    <textarea 
                        id="feedbackContent"
                        name="content"
                        rows="4"
                        maxlength="1000"
                        x-model="content"
                        required
                        placeholder="{{ __('global.feedback.content_placeholder') }}"
                        class="w-full p-3 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#006948] focus:ring-1 focus:ring-[#006948] transition-colors resize-none"
                    ></textarea>
                </div>

                <!-- Quota indicator callout -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-[#006948] text-[20px] mt-0.5 shrink-0">info</span>
                    <div class="text-xs text-slate-600">
                        <strong class="font-semibold text-slate-800">
                            {{ __('global.feedback.daily_quota', ['count' => $todayCount]) }}
                        </strong>
                        <p class="mt-0.5 text-slate-500">
                            {{ __('global.feedback.daily_quota_desc') }}
                        </p>
                    </div>
                </div>

                <!-- Anti-Spam Captcha Section -->
                @if(\App\Support\Helpers\CaptchaHelper::isCaptchaEnabled())
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="feedback-captcha">
                        {{ __('global.feedback.captcha_label') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div id="captcha-img-wrapper"
                                 data-captcha-api="{{ url('/captcha/api/contact') }}"
                                 data-captcha-fallback="{{ captcha_src('contact') }}"
                                 data-loading-text="{{ __('global.feedback.captcha_loading') }}"
                                 class="w-[120px] h-[38px] rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden cursor-pointer shrink-0 shadow-2xs hover:border-[#006948]/50 transition-colors"
                                 title="{{ __('global.feedback.captcha_refresh') }}">
                                {!! captcha_img('contact') !!}
                            </div>
                            <button type="button"
                                    id="refresh-captcha-btn"
                                    class="w-[38px] h-[38px] rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-[#006948] flex items-center justify-center transition-colors cursor-pointer shrink-0 shadow-2xs"
                                    title="{{ __('global.feedback.captcha_refresh') }}">
                                <span class="material-symbols-outlined text-[18px] transition-transform duration-300">sync</span>
                            </button>
                        </div>
                        <input class="flex-1 w-full h-[38px] px-3 rounded-lg border @error('captcha') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-slate-800 text-sm focus:bg-white focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all placeholder:text-slate-400"
                               id="feedback-captcha"
                               name="captcha"
                               type="text"
                               maxlength="6"
                               required
                               placeholder="{{ __('global.feedback.captcha_placeholder') }}">
                    </div>
                    @error('captcha')
                        <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                @endif

                <!-- Action buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button 
                        type="reset" 
                        class="h-9 px-4 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 transition-colors"
                    >
                        {{ __('global.feedback.reset_btn') }}
                    </button>
                    <button 
                        type="submit" 
                        @if(!$canSubmit) disabled @endif
                        class="h-9 px-5 rounded-xl bg-[#006948] hover:bg-[#005137] text-white text-xs font-semibold flex items-center gap-1.5 transition-colors focus:outline-none focus:ring-2 focus:ring-[#006948] shadow-xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[17px]">send</span>
                        <span>{{ __('global.feedback.send_btn') }}</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Section 2: Đánh giá & Phản hồi cộng đồng nội bộ (Community Reviews & Sentiment) -->
        <section class="lg:col-span-7 space-y-5">
            <!-- Top Aggregate Metric Box -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#006948]">analytics</span>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('global.feedback.satisfaction_stats') }}</h2>
                    </div>
                    <span class="text-xs text-slate-500">{{ __('global.feedback.updated_today') }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <!-- Điểm trung bình -->
                    <div class="md:col-span-4 flex flex-col items-center md:items-start border-b md:border-b-0 md:border-r border-slate-200 pb-4 md:pb-0 md:pr-4">
                        <div class="text-[40px] font-bold text-slate-900 leading-none tracking-tight">
                            {{ number_format($avgScore, 1) }} <span class="text-base text-slate-400 font-normal">/ 5</span>
                        </div>
                        <div class="flex items-center gap-0.5 text-[#006948] my-1.5">
                            @for($i = 1; $i <= 5; $i++)
                                @if($avgScore >= $i)
                                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                @elseif($avgScore >= ($i - 0.5))
                                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">star_half</span>
                                @else
                                    <span class="material-symbols-outlined text-[18px] text-slate-300">star</span>
                                @endif
                            @endfor
                        </div>
                        <span class="text-xs text-slate-500 text-center md:text-left">
                            {{ __('global.feedback.based_on_reviews', ['count' => $totalCount]) }}
                        </span>
                    </div>

                    <!-- Rating breakdown bar chart -->
                    <div class="md:col-span-8 space-y-1.5">
                        @foreach([5, 4, 3, 2, 1] as $s)
                            @php
                                $barPercent = $countsByStar[$s]['percent'] ?? 0;
                            @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-10 text-slate-600 font-medium">{{ __('global.feedback.star_count', ['star' => $s]) }}</span>
                                <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-300 {{ $s >= 4 ? 'bg-[#006948]' : ($s === 3 ? 'bg-amber-400' : 'bg-slate-300') }}" 
                                        style="width: {{ $barPercent }}%;"
                                    ></div>
                                </div>
                                <span class="w-8 text-right font-mono text-slate-700 font-semibold">{{ $barPercent }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Highlight Metric: Tỷ lệ hài lòng -->
                @php
                    $positiveCount = ($countsByStar[5]['count'] ?? 0) + ($countsByStar[4]['count'] ?? 0);
                    $positiveRate = $totalCount > 0 ? round(($positiveCount / $totalCount) * 100, 1) : 0;
                @endphp
                <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-[20px]" style="font-variation-settings: 'FILL' 1;">verified</span>
                        <span class="text-xs text-slate-700">{{ __('global.feedback.positive_rate_label') }}</span>
                    </div>
                    @if($totalCount > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            {{ __('global.feedback.positive_rate_val', ['rate' => $positiveRate]) }}
                        </span>
                    @else
                        <span class="text-xs text-slate-400">{{ __('global.feedback.no_stats') }}</span>
                    @endif
                </div>
            </div>

            <!-- Feed danh sách góp ý tiêu biểu (Recent Reviews) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-slate-400">chat_bubble_outline</span>
                        {{ __('global.feedback.recent_feedbacks') }}
                    </h3>
                    <span id="showing-feedbacks-count" class="text-xs text-slate-400" data-total="{{ $feedbacks->total() }}" data-current="{{ $feedbacks->count() }}">
                        {{ __('global.feedback.showing_feedbacks', ['count' => $feedbacks->count()]) }} / {{ $feedbacks->total() }}
                    </span>
                </div>

                <div id="feedbacks-container" class="space-y-3">
                    @if($feedbacks->count() > 0)
                        @foreach($feedbacks as $fb)
                            <article class="feedback-item bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs hover:border-slate-300 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center border border-emerald-200 shrink-0">
                                            {{ strtoupper(mb_substr($fb->user_display_name ?: 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-semibold text-slate-800">{{ $fb->user_display_name ?: __('global.feedback.anonymous_user') }}</h4>
                                            <span class="text-[11px] text-slate-400">{{ $fb->created_at->format('d/m/Y') }} · {{ $fb->subsystem_label }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-0.5 text-[#006948]">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' {{ $i <= $fb->rating ? 1 : 0 }}; {{ $i > $fb->rating ? 'color: #cbd5e1;' : '' }}">star</span>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    "{{ $fb->content }}"
                                </p>
                            </article>
                        @endforeach
                    @else
                        <div id="empty-feedbacks-box" class="bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-2xs">
                            <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-[24px]">chat_bubble_outline</span>
                            </div>
                            <h4 class="text-xs font-semibold text-slate-700">{{ __('global.feedback.empty_feedbacks_title') }}</h4>
                            <p class="text-[11px] text-slate-400 mt-1 max-w-xs mx-auto">{{ __('global.feedback.empty_feedbacks_desc') }}</p>
                        </div>
                    @endif
                </div>

                <!-- Load More Button -->
                @if($feedbacks->hasMorePages())
                    <div class="pt-2 text-center" id="load-more-wrapper">
                        <button type="button"
                                id="load-more-feedbacks-btn"
                                data-next-page="2"
                                data-url="{{ route('user.me.feedback') }}"
                                data-loading-text="{{ __('global.feedback.loading_more') }}"
                                data-all-loaded-text="{{ __('global.feedback.all_loaded') }}"
                                data-showing-text="{{ __('global.feedback.showing_feedbacks', ['count' => '__COUNT__']) }}"
                                class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:border-[#006948] bg-white hover:bg-emerald-50/50 text-slate-700 hover:text-[#006948] text-xs font-semibold transition-all shadow-2xs flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                            <span id="load-more-text">{{ __('global.feedback.load_more_btn') }}</span>
                        </button>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
</x-global.layout>
