@props(['articles' => []])

<div
  x-data="{
    search: '',
    viewMode: 'grid',
    articles: {{ Js::from(array_values($articles)) }},
    normalizeText(text) {
      // Không phân biệt hoa thường và dấu tiếng Việt (đ/Đ được quy về d).
      return String(text || '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase();
    },
    get filteredArticles() {
      const q = this.normalizeText(this.search).trim();
      if (!q) return this.articles;
      return this.articles.filter(a => this.normalizeText(a.title + ' ' + a.excerpt).includes(q));
    }
  }"
>
  <!-- Search bar + View mode toggle (nằm ngang) -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-4">
    <div class="relative flex-1 max-w-md">
      <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
        <span class="material-symbols-outlined text-[18px]">search</span>
      </span>
      <input
        type="text"
        x-model.debounce.400ms="search"
        class="w-full pl-9 pr-9 h-10 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-colors"
        placeholder="{{ __('guides.search_placeholder') }}"
      >
      <button
        type="button"
        x-show="search"
        x-cloak
        @click="search = ''"
        class="absolute inset-y-0 right-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
        title="{{ __('guides.clear_search') }}"
        aria-label="{{ __('guides.clear_search') }}"
      >
        <span class="material-symbols-outlined text-[16px]">close</span>
      </button>
    </div>

    <!-- View mode toggle: Grid / List -->
    <div class="inline-flex p-1 bg-slate-100 rounded-lg border border-slate-200 shrink-0 self-start sm:self-auto" role="tablist">
      <button
        type="button"
        @click="viewMode = 'grid'"
        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer"
        :class="viewMode === 'grid' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
        role="tab"
        :aria-selected="viewMode === 'grid' ? 'true' : 'false'"
        title="{{ __('guides.view_grid') }}"
        aria-label="{{ __('guides.view_grid') }}"
      >
        <span class="material-symbols-outlined text-[16px]">grid_view</span>
        <span class="hidden sm:inline">{{ __('guides.view_grid') }}</span>
      </button>
      <button
        type="button"
        @click="viewMode = 'list'"
        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer"
        :class="viewMode === 'list' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
        role="tab"
        :aria-selected="viewMode === 'list' ? 'true' : 'false'"
        title="{{ __('guides.view_list') }}"
        aria-label="{{ __('guides.view_list') }}"
      >
        <span class="material-symbols-outlined text-[16px]">view_list</span>
        <span class="hidden sm:inline">{{ __('guides.view_list') }}</span>
      </button>
    </div>
  </div>

  <p class="text-xs text-slate-500 mb-4" x-text="'{{ __('guides.results_count', ['count' => '§']) }}'.replace('§', filteredArticles.length)"></p>

  <!-- Article Card Grid (3 columns on desktop) -->
  <div x-show="viewMode === 'grid'" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <template x-for="article in filteredArticles" :key="article.slug">
      <a :href="article.url" class="group flex flex-col bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-emerald-300 transition-all">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center mb-3 shrink-0">
          <span class="material-symbols-outlined text-[20px]">article</span>
        </div>
        <h3 class="text-sm font-bold text-slate-900 group-hover:text-[#006948] transition-colors line-clamp-2" x-text="article.title"></h3>
        <p class="text-xs text-slate-500 mt-1.5 line-clamp-3 flex-1" x-text="article.excerpt"></p>
        <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#006948]">
          {{ __('guides.read_more') }}
          <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
        </span>
      </a>
    </template>
  </div>

  <!-- Article List (1 column, hàng ngang) -->
  <div x-show="viewMode === 'list'" x-cloak class="flex flex-col divide-y divide-slate-100 bg-white rounded-xl border border-slate-200/80 overflow-hidden">
    <template x-for="article in filteredArticles" :key="article.slug">
      <a :href="article.url" class="group flex items-center gap-4 p-4 hover:bg-emerald-50/40 transition-colors">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-[20px]">article</span>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-sm font-bold text-slate-900 group-hover:text-[#006948] transition-colors truncate" x-text="article.title"></h3>
          <p class="text-xs text-slate-500 mt-0.5 line-clamp-1" x-text="article.excerpt"></p>
        </div>
        <span class="material-symbols-outlined text-[18px] text-slate-300 group-hover:text-[#006948] group-hover:translate-x-0.5 transition-all shrink-0">chevron_right</span>
      </a>
    </template>
  </div>

  <!-- Empty State -->
  <div x-show="filteredArticles.length === 0" x-cloak class="py-16 px-6 text-center bg-white rounded-xl border border-slate-200">
    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#006948] flex items-center justify-center mx-auto mb-4">
      <span class="material-symbols-outlined text-[32px]">search_off</span>
    </div>
    <h3 class="text-base font-bold text-slate-900">{{ __('guides.empty_title') }}</h3>
    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">{{ __('guides.empty_desc') }}</p>
    <div class="mt-4" x-show="search" x-cloak>
      <button type="button" @click="search = ''" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[16px]">clear_all</span>
        <span>{{ __('guides.clear_search') }}</span>
      </button>
    </div>
  </div>
</div>
