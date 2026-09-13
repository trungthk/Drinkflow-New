<x-public.layout
    activeTab="about"
    :version="$version ?? 'v2.3.0'"
    :termsUrl="$termsUrl ?? url('/terms')"
    :versionsUrl="$versionsUrl ?? url('/versions')"
    :contactUrl="$contactUrl ?? route('contact')"
    :googleAuthUrl="$googleAuthUrl ?? route('auth.google')"
>
    <x-slot:head>
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </x-slot:head>

    <!-- MAIN CANVAS -->
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-6 py-10 flex flex-col gap-16">
        <!-- 1. HERO SECTION -->
        <x-public.hero
            :version="$version ?? 'v2.3.0'"
            :googleAuthUrl="$googleAuthUrl ?? route('auth.google')"
        />

        <!-- 2. PROBLEM VS SOLUTION -->
        <x-public.problem-solution />

        <!-- 3. KEY BENEFITS (4 Columns) -->
        <section class="border-t border-[#E2E8F0] pt-12">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-2xl font-bold text-[#0b1c30]">{{ __('public.benefits.title') }}</h2>
                <p class="text-slate-500 text-sm mt-2">{{ __('public.benefits.subtitle') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-public.benefit-card
                    icon="touch_app"
                    :title="__('public.benefits.b1_title')"
                    :description="__('public.benefits.b1_desc')"
                />
                <x-public.benefit-card
                    icon="fact_check"
                    :title="__('public.benefits.b2_title')"
                    :description="__('public.benefits.b2_desc')"
                />
                <x-public.benefit-card
                    icon="bolt"
                    :title="__('public.benefits.b3_title')"
                    :description="__('public.benefits.b3_desc')"
                />
                <x-public.benefit-card
                    icon="qr_code_scanner"
                    :title="__('public.benefits.b4_title')"
                    :description="__('public.benefits.b4_desc')"
                />
            </div>
        </section>

        <!-- 4. CORE FEATURES (6 Grid Cards) -->
        <section class="border-t border-[#E2E8F0] pt-12">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-semibold text-[#006948] uppercase tracking-wider bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">{{ __('public.features.eyebrow') }}</span>
                <h2 class="text-2xl font-bold text-[#0b1c30] mt-3">{{ __('public.features.title') }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-public.feature-card
                    icon="dashboard"
                    :title="__('public.features.f1_title')"
                    :description="__('public.features.f1_desc')"
                />
                <x-public.feature-card
                    icon="receipt_long"
                    :title="__('public.features.f2_title')"
                    :description="__('public.features.f2_desc')"
                />
                <x-public.feature-card
                    icon="schedule"
                    :title="__('public.features.f3_title')"
                    :description="__('public.features.f3_desc')"
                />
                <x-public.feature-card
                    icon="group"
                    :title="__('public.features.f4_title')"
                    :description="__('public.features.f4_desc')"
                />
                <x-public.feature-card
                    icon="account_balance_wallet"
                    :title="__('public.features.f5_title')"
                    :description="__('public.features.f5_desc')"
                />
                <x-public.feature-card
                    icon="meeting_room"
                    :title="__('public.features.f6_title')"
                    :description="__('public.features.f6_desc')"
                />
            </div>
        </section>

        <!-- 5. HOW IT WORKS -->
        <x-public.how-it-works />

        <!-- 6. GOOGLE SSO HIGHLIGHT -->
        <x-public.google-sso />

        <!-- 7. FINAL CTA -->
        <x-public.cta
            :googleAuthUrl="$googleAuthUrl ?? route('auth.google')"
        />
    </main>

    <!-- VIDEO INTRO MODAL -->
    <x-public.video-modal />
</x-public.layout>
