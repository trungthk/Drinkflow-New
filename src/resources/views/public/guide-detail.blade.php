@php
    $isAgent = $audience === \App\Services\Guide\UserGuideService::AUDIENCE_AGENT;
    $listTitle = $isAgent ? __('guides.agent_page_title') : __('guides.page_title');
    $fallbackDescription = $isAgent ? __('guides.agent_public_meta_description') : __('guides.public_meta_description');
    $articleUrl = $indexUrl . '/' . $article['slug'];
    $jsonLd = [
        [
            '@type' => 'TechArticle',
            'headline' => $article['title'],
            'description' => $article['excerpt'],
            'url' => $articleUrl,
            'inLanguage' => 'vi',
            'mainEntityOfPage' => $articleUrl,
            'publisher' => ['@id' => url('/') . '#organization'],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.header.about'), 'item' => \App\Support\Helpers\LocaleUrl::url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $listTitle, 'item' => $indexUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title'], 'item' => $articleUrl],
            ],
        ],
    ];
@endphp
<x-public.layout
    :title="$article['title'] . ' - DrinkFlow'"
    :description="$article['excerpt'] !== '' ? $article['excerpt'] : $fallbackDescription"
    :ogTitle="$article['title']"
    ogType="article"
    activeTab="guides"
    :jsonLd="$jsonLd"
>
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-5">
        {{-- Nút quay lại và nhóm chuyển đổi bộ hướng dẫn nằm ở hai bên (không dồn về trái). --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $indexUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#006948] hover:text-[#005137] transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                {{ __('guides.back_to_list') }}
            </a>

            <nav class="inline-flex p-1 rounded-xl border border-slate-200 bg-white shadow-sm" role="tablist" aria-label="{{ __('guides.audience_toggle_label') }}">
                <a href="{{ $clientUrl }}" role="tab" @if ($audience === \App\Services\Guide\UserGuideService::AUDIENCE_CLIENT) aria-current="page" @endif
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold transition-colors {{ $audience === \App\Services\Guide\UserGuideService::AUDIENCE_CLIENT ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span class="material-symbols-outlined text-[16px]">person</span>{{ __('guides.audience_client') }}
                </a>
                <a href="{{ $agentUrl }}" role="tab" @if ($audience === \App\Services\Guide\UserGuideService::AUDIENCE_AGENT) aria-current="page" @endif
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold transition-colors {{ $audience === \App\Services\Guide\UserGuideService::AUDIENCE_AGENT ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span class="material-symbols-outlined text-[16px]">storefront</span>{{ __('guides.audience_agent') }}
                </a>
            </nav>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="guide-article">
                {!! $article['html'] !!}
            </div>
        </article>
        <x-image-lightbox :title="__('guides.lightbox_title')" />
    </main>
</x-public.layout>
