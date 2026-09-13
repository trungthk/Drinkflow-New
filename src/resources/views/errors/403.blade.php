<x-public.layout :title="__('errors.403.page_title')">
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-6 py-8" x-data="{
        showModal: false,
        copied: false,
        tokenId: 'perm-chk-403-' + Math.random().toString(36).substring(2, 7),
        copyToken() {
            navigator.clipboard.writeText(this.tokenId);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        submitRequest() {
            alert('{{ __('errors.403.modal_desc') }}');
            this.showModal = false;
        }
    }">
        <!-- BREADCRUMB -->
        <div class="flex items-center gap-2 text-on-surface-variant font-body-sm text-body-sm mb-6">
            <a class="hover:text-primary flex items-center gap-1 transition-colors" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-[16px]">home</span>
                <span>{{ __('errors.common.back_home') }}</span>
            </a>
            <span class="text-outline-variant">/</span>
            <span class="text-on-surface font-medium">{{ __('errors.403.badge_main') }}</span>
        </div>

        <!-- BANNER KHỐI MÃ LỖI HTTP 403 -->
        <section class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-6 sm:p-8 shadow-sm mb-8 relative overflow-hidden">
            <!-- Decorative Security Watermark -->
            <div class="absolute -right-8 -top-8 text-surface-container select-none pointer-events-none opacity-40">
                <span class="material-symbols-outlined text-[190px]">gpp_maybe</span>
            </div>

            <div class="relative z-10">
                <!-- Status Badges -->
                <div class="flex flex-wrap items-center gap-2 mb-5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-caption font-caption font-semibold bg-error-container text-on-error-container">
                        <span class="w-2 h-2 rounded-full bg-error"></span>
                        {{ __('errors.403.badge_main') }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-caption font-caption font-medium bg-surface-container text-on-surface-variant border border-outline-variant">
                        <span class="material-symbols-outlined text-[14px]">shield</span>
                        {{ __('errors.403.badge_sub') }}
                    </span>
                </div>

                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="max-w-3xl">
                        <!-- Numeric Code & Icon -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-lg bg-surface-container-high text-primary flex items-center justify-center border border-outline-variant">
                                <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">lock</span>
                            </div>
                            <div>
                                <span class="font-headline-xl text-headline-xl font-bold tracking-tight text-on-surface block leading-none">403</span>
                                <span class="font-caption text-caption text-on-surface-variant uppercase tracking-wider font-semibold">{{ __('errors.403.clearance_failed') }}</span>
                            </div>
                        </div>

                        <!-- Title & Description -->
                        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2 font-bold">
                            {{ __('errors.403.title') }}
                        </h1>
                        <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                            {{ __('errors.403.description') }}
                        </p>
                    </div>

                    <!-- Quick Actions Button Group -->
                    <div class="flex flex-col sm:flex-row lg:flex-col gap-2.5 w-full lg:w-auto shrink-0">
                        <button class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-on-primary rounded-lg font-label-md text-label-md font-medium hover:bg-primary-container transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" @click="showModal = true" type="button">
                            <span class="material-symbols-outlined text-[18px]">key</span>
                            <span>{{ __('errors.403.request_access') }}</span>
                        </button>
                        <a class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-surface-container-lowest border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md font-medium hover:bg-surface-container-low transition-colors shadow-sm" href="{{ url('/me/rooms') }}">
                            <span class="material-symbols-outlined text-[18px]">meeting_room</span>
                            <span>{{ __('errors.403.my_rooms') }}</span>
                        </a>
                        <a class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-surface text-on-surface-variant hover:text-on-surface rounded-lg font-label-sm text-label-sm border border-transparent hover:border-outline-variant transition-colors" href="{{ route('auth.google') }}">
                            <span class="material-symbols-outlined text-[16px]">switch_account</span>
                            <span>{{ __('errors.403.switch_account') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- DIAGNOSTICS & MATRIX WORKSPACE -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <!-- Column 1: Causes & Recommendations -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 border-b border-outline-variant pb-3 mb-4">
                        <span class="material-symbols-outlined text-tertiary text-[20px]">lightbulb</span>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('errors.403.causes_title') }}</h3>
                    </div>
                    <div class="space-y-4 font-body-sm text-body-sm">
                        <div class="p-3.5 rounded-lg bg-surface-container-low border border-outline-variant/80">
                            <div class="flex items-start gap-2 mb-1">
                                <span class="w-5 h-5 rounded-full bg-surface-container flex items-center justify-center font-caption text-caption font-bold text-on-surface shrink-0">1</span>
                                <h4 class="font-label-md text-label-md font-semibold text-on-surface">{{ __('errors.403.cause_1_title') }}</h4>
                            </div>
                            <p class="text-on-surface-variant pl-7">{{ __('errors.403.cause_1_desc') }}</p>
                        </div>
                        <div class="p-3.5 rounded-lg bg-surface-container-low border border-outline-variant/80">
                            <div class="flex items-start gap-2 mb-1">
                                <span class="w-5 h-5 rounded-full bg-surface-container flex items-center justify-center font-caption text-caption font-bold text-on-surface shrink-0">2</span>
                                <h4 class="font-label-md text-label-md font-semibold text-on-surface">{{ __('errors.403.cause_2_title') }}</h4>
                            </div>
                            <p class="text-on-surface-variant pl-7">{{ __('errors.403.cause_2_desc') }}</p>
                        </div>
                        <div class="p-3.5 rounded-lg bg-surface-container-low border border-outline-variant/80">
                            <div class="flex items-start gap-2 mb-1">
                                <span class="w-5 h-5 rounded-full bg-surface-container flex items-center justify-center font-caption text-caption font-bold text-on-surface shrink-0">3</span>
                                <h4 class="font-label-md text-label-md font-semibold text-on-surface">{{ __('errors.403.cause_3_title') }}</h4>
                            </div>
                            <p class="text-on-surface-variant pl-7">{{ __('errors.403.cause_3_desc') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Column 2: Support Info -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 border-b border-outline-variant pb-3 mb-4">
                        <span class="material-symbols-outlined text-primary text-[20px]">support_agent</span>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('errors.403.support_title') }}</h3>
                    </div>
                    <div class="space-y-4 font-body-sm text-body-sm">
                        <div class="space-y-2">
                            <span class="text-caption font-caption text-on-surface-variant block uppercase tracking-wider font-medium">{{ __('errors.403.support_channel') }}</span>
                            <div class="flex items-center justify-between text-on-surface p-2.5 rounded-lg bg-surface-container-low border border-outline-variant/80">
                                <span class="flex items-center gap-1.5 text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px]">chat</span> {{ __('errors.403.slack_channel') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-on-surface p-2.5 rounded-lg bg-surface-container-low border border-outline-variant/80">
                                <span class="flex items-center gap-1.5 text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px]">call</span> {{ __('errors.403.hotline_label') }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-caption font-caption text-on-surface-variant block uppercase tracking-wider font-medium mb-1.5">{{ __('errors.403.session_token') }}</span>
                            <div class="flex items-center justify-between bg-surface-container-low border border-outline-variant rounded-lg p-2.5 font-mono text-caption text-on-surface">
                                <span x-text="tokenId"></span>
                                <button class="p-1 hover:bg-surface-container rounded text-primary transition-colors flex items-center gap-1" @click="copyToken()" type="button" title="{{ __('errors.403.copy_token') }}">
                                    <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                    <span class="text-xs" x-show="copied">{{ __('errors.403.copied') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- MODAL DIALOG: REQUEST ACCESS -->
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#0F172A]/40 backdrop-blur-[2px]" x-show="showModal" x-cloak>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-xl w-full max-w-lg p-6 relative" @click.outside="showModal = false">
                <div class="flex items-center justify-between border-b border-outline-variant pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">key</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ __('errors.403.modal_title') }}</h3>
                    </div>
                    <button class="p-1 text-on-surface-variant hover:text-on-surface rounded-lg transition-colors" @click="showModal = false" type="button">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="space-y-3 mb-4 font-body-sm text-body-sm text-on-surface-variant">
                    <p>{{ __('errors.403.modal_desc') }}</p>
                    <div>
                        <label class="block font-label-md text-label-md font-medium text-on-surface mb-1">{{ __('errors.403.modal_notes') }}</label>
                        <textarea class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-2.5 text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:outline-none" placeholder="{{ __('errors.403.modal_notes_placeholder') }}" rows="3"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant">
                    <button class="px-4 py-2 bg-surface border border-outline-variant rounded-lg text-on-surface font-label-md text-label-md hover:bg-surface-container transition-colors" @click="showModal = false" type="button">
                        {{ __('errors.403.modal_cancel') }}
                    </button>
                    <button class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md font-medium hover:bg-primary-container transition-colors shadow-sm" @click="submitRequest()" type="button">
                        {{ __('errors.403.modal_submit') }}
                    </button>
                </div>
            </div>
        </div>
    </main>
</x-public.layout>
