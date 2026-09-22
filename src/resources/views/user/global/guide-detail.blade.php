<x-global.layout
  :title="'DrinkFlow - ' . $article['title']"
  :user="$user"
  activeTab="guides"
  :breadcrumbs="$breadcrumbs"
  :unreadNotificationsCount="$unreadNotificationsCount ?? 0"
  :notifications="$notifications ?? collect()"
>
  <div class="space-y-5">
    <a href="{{ $indexUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#006948] hover:text-[#005137] transition-colors">
      <span class="material-symbols-outlined text-[16px]">arrow_back</span>
      {{ __('guides.back_to_list') }}
    </a>

    <article class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
      <div class="guide-article">
        {!! $article['html'] !!}
      </div>
    </article>

    <a href="{{ $indexUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#006948] hover:text-[#005137] transition-colors">
      <span class="material-symbols-outlined text-[16px]">arrow_back</span>
      {{ __('guides.back_to_list') }}
    </a>
  </div>
</x-global.layout>
