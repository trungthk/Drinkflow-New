<x-room.layout :room="$room" :active-tab="'guides'" :title="'DrinkFlow - ' . __('guides.page_title')">
  <div class="space-y-6">
    <section class="pb-6 border-b border-slate-200">
      <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ __('guides.page_title') }}</h1>
      <p class="text-sm text-slate-500 mt-1">{{ __('guides.page_subtitle') }}</p>
    </section>

    <x-guides.article-grid :articles="$articles" />
  </div>
</x-room.layout>
