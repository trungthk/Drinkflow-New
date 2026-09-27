@php
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
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('guides.page_title'), 'item' => $indexUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title'], 'item' => $articleUrl],
            ],
        ],
    ];
@endphp
<x-public.layout
    :title="$article['title'] . ' - DrinkFlow'"
    :description="$article['excerpt'] !== '' ? $article['excerpt'] : __('guides.public_meta_description')"
    :ogTitle="$article['title']"
    ogType="article"
    activeTab="guides"
    :jsonLd="$jsonLd"
>
    <main class="flex-1 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-5">
        <a href="{{ $indexUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#006948] hover:text-[#005137] transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            {{ __('guides.back_to_list') }}
        </a>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="guide-article">
                {!! $article['html'] !!}
            </div>
        </article>
        <x-image-lightbox :title="__('guides.lightbox_title')" />
    </main>
</x-public.layout>
