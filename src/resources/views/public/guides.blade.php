@php
    $jsonLd = [
        [
            '@type' => 'CollectionPage',
            'name' => __('guides.public_meta_title'),
            'url' => $indexUrl,
            'inLanguage' => 'vi',
            'hasPart' => collect($articles)->map(fn (array $a): array => ['@type' => 'TechArticle', 'headline' => $a['title'], 'url' => $a['url']])->all(),
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.header.about'), 'item' => \App\Support\Helpers\LocaleUrl::url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('guides.page_title'), 'item' => $indexUrl],
            ],
        ],
    ];
@endphp
<x-public.layout
    :title="__('guides.public_meta_title')"
    :description="__('guides.public_meta_description')"
    activeTab="guides"
    :jsonLd="$jsonLd"
>
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <header class="mb-8 max-w-3xl">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-[#0F172A] tracking-tight">{{ __('guides.page_title') }}</h1>
            <p class="mt-3 text-base text-[#475569]">{{ __('guides.page_subtitle') }}</p>
        </header>

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
    </main>
</x-public.layout>
