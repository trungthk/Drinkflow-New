@php
    $isAgent = $audience === \App\Services\Guide\UserGuideService::AUDIENCE_AGENT;
    $pageTitle = $isAgent ? __('guides.agent_page_title') : __('guides.page_title');
    $pageSubtitle = $isAgent ? __('guides.agent_page_subtitle') : __('guides.page_subtitle');
    $metaTitle = $isAgent ? __('guides.agent_public_meta_title') : __('guides.public_meta_title');
    $metaDescription = $isAgent ? __('guides.agent_public_meta_description') : __('guides.public_meta_description');
    $jsonLd = [
        [
            '@type' => 'CollectionPage',
            'name' => $metaTitle,
            'url' => $indexUrl,
            'inLanguage' => 'vi',
            'hasPart' => collect($articles)->map(fn (array $a): array => ['@type' => 'TechArticle', 'headline' => $a['title'], 'url' => $a['url']])->all(),
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.header.about'), 'item' => \App\Support\Helpers\LocaleUrl::url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $pageTitle, 'item' => $indexUrl],
            ],
        ],
    ];
@endphp
<x-public.layout
    :title="$metaTitle"
    :description="$metaDescription"
    activeTab="guides"
    :jsonLd="$jsonLd"
>
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <header class="mb-6 max-w-3xl">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-[#0F172A] tracking-tight">{{ $pageTitle }}</h1>
            <p class="mt-3 text-base text-[#475569]">{{ $pageSubtitle }}</p>
        </header>

        {{-- Chuyển đổi giữa bộ hướng dẫn Client và bộ hướng dẫn Đại lý (Agent). --}}
        <nav class="mb-8 inline-flex p-1 rounded-xl border border-slate-200 bg-white shadow-sm" role="tablist" aria-label="{{ __('guides.audience_toggle_label') }}">
            <a href="{{ $clientUrl }}" role="tab" @if (! $isAgent) aria-current="page" @endif
                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold transition-colors {{ ! $isAgent ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                <span class="material-symbols-outlined text-[16px]">person</span>{{ __('guides.audience_client') }}
            </a>
            <a href="{{ $agentUrl }}" role="tab" @if ($isAgent) aria-current="page" @endif
                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold transition-colors {{ $isAgent ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                <span class="material-symbols-outlined text-[16px]">storefront</span>{{ __('guides.audience_agent') }}
            </a>
        </nav>

        @if ($articles === [])
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h2 class="text-base font-bold text-[#0F172A]">{{ __('guides.empty_title') }}</h2>
                <p class="mt-1 text-sm text-[#64748b]">{{ __('guides.empty_desc') }}</p>
            </div>
        @else
            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($articles as $article)
                    <li>
                        <a href="{{ $article['url'] }}" class="group flex h-full flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-colors hover:border-[#006948]">
                            <span class="text-xs font-semibold uppercase tracking-wider text-[#006948]">{{ str_pad((string) $article['number'], 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 class="text-lg font-semibold text-[#0F172A] group-hover:text-[#006948]">{{ $article['title'] }}</h2>
                            <p class="text-sm text-[#475569]">{{ $article['excerpt'] }}</p>
                            <span class="mt-auto inline-flex items-center gap-1 pt-2 text-xs font-semibold text-[#006948]">
                                {{ __('guides.read_more') }}
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
</x-public.layout>
