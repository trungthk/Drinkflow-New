<x-global.layout
  :title="'DrinkFlow - ' . __('global.rooms.title')"
  :user="$user"
  :active-tab="'rooms'"
  :breadcrumbs="$breadcrumbs"
  :unread-notifications-count="$unreadNotificationsCount"
  :notifications="$notifications"
>
  <div class="space-y-6">
    
    <!-- Status & Error Flash Alerts -->
    @if(session('status'))
      <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm font-medium flex items-center justify-between shadow-xs transition-all animate-fadeIn">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-emerald-100 text-[#006948] flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
          </span>
          <span>{{ session('status') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 p-1 rounded-lg hover:bg-emerald-100/60 transition-colors cursor-pointer" title="{{ __('global.common.close') }}">
          <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
      </div>
    @endif

    @if($errors->any())
      <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200/80 text-red-900 text-sm font-medium flex items-center justify-between shadow-xs transition-all animate-fadeIn">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[18px]">error</span>
          </span>
          <span>{{ $errors->first() }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-950 p-1 rounded-lg hover:bg-red-100/60 transition-colors cursor-pointer" title="{{ __('global.common.close') }}">
          <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
      </div>
    @endif

    <!-- Page Header & Control Bar -->
    <section class="mb-8">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-slate-200">
        <div>
          <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('user.me.dashboard') }}" class="hover:text-slate-700 transition-colors">{{ __('global.rooms.breadcrumb_personal') }}</a>
            <span>/</span>
            <span class="text-[#006948] font-medium">{{ __('global.rooms.breadcrumb_rooms') }}</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ __('global.rooms.title') }}</h1>
          <p class="text-sm text-slate-500 mt-1">
            {{ __('global.rooms.subtitle') }}
          </p>
        </div>

        <!-- Search and Filters Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <!-- Debounced Search bar with clear [X] button -->
          <form method="GET" action="{{ route('user.me.rooms') }}" class="relative flex-1 sm:w-80">
            @if($filter !== 'all')
              <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            @if($sort !== 'latest')
              <input type="hidden" name="sort" value="{{ $sort }}">
            @endif
            <x-global.search-input
              name="q"
              :value="$search"
              :placeholder="__('global.search.room_placeholder')"
              id="room-search-input"
              :debounce="400"
            />
          </form>

          <!-- Filter Tabs -->
          <div class="inline-flex p-1 bg-slate-100 rounded-lg border border-slate-200 shrink-0" role="tablist">
            <a href="{{ route('user.me.rooms', array_filter(['q' => $search, 'filter' => 'all', 'sort' => $sort !== 'latest' ? $sort : null])) }}"
               class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
              {{ __('global.rooms.filter_all', ['count' => $totalCount]) }}
            </a>
            <a href="{{ route('user.me.rooms', array_filter(['q' => $search, 'filter' => 'active', 'sort' => $sort !== 'latest' ? $sort : null])) }}"
               class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $filter === 'active' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
              {{ __('global.rooms.filter_active', ['count' => $activeCount]) }}
            </a>
            <a href="{{ route('user.me.rooms', array_filter(['q' => $search, 'filter' => 'restricted', 'sort' => $sort !== 'latest' ? $sort : null])) }}"
               class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $filter === 'restricted' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
              {{ __('global.rooms.filter_restricted', ['count' => $restrictedCount]) }}
            </a>
          </div>

          <!-- Sort Select -->
          <form method="GET" action="{{ route('user.me.rooms') }}" id="sort-form" class="shrink-0">
            @if(!empty($search))
              <input type="hidden" name="q" value="{{ $search }}">
            @endif
            @if($filter !== 'all')
              <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <div class="relative">
              <select name="sort"
                      onchange="document.getElementById('sort-form').submit()"
                      class="h-[38px] pl-8 pr-7 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:border-slate-300 focus:border-[#006948] focus:ring-1 focus:ring-[#006948] cursor-pointer appearance-none outline-none">
                <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>{{ __('global.rooms.sort_latest') }}</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>{{ __('global.rooms.sort_oldest') }}</option>
                <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>{{ __('global.rooms.sort_name_asc') }}</option>
                <option value="spent_desc" {{ $sort === 'spent_desc' ? 'selected' : '' }}>{{ __('global.rooms.sort_spent_desc') }}</option>
              </select>
              <span class="absolute inset-y-0 left-2.5 flex items-center text-slate-400 pointer-events-none" aria-hidden="true">
                <span class="material-symbols-outlined text-[16px]">swap_vert</span>
              </span>
              <span class="absolute inset-y-0 right-2 flex items-center text-slate-400 pointer-events-none" aria-hidden="true">
                <span class="material-symbols-outlined text-[16px]">expand_more</span>
              </span>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Rooms Bento / Card Grid (3 Columns) -->
    <section class="mb-10">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($roomUsers as $item)
          @if($item->is_active)
            <x-global.room-card :item="$item" :show-live-status="true" />
          @else
            <!-- CARD: Restricted / Blocked Room -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between opacity-95 relative overflow-hidden">
              <!-- Top Subdued Texture Bar -->
              <div class="absolute top-0 left-0 right-0 h-1 bg-slate-200"></div>
              <div>
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-3 mb-4">
                  <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                    <span class="material-symbols-outlined text-[24px]">lock</span>
                  </div>
                  <!-- Badge: Bị hạn chế -->
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F1F5F9] text-slate-600 border border-slate-200">
                    <span class="material-symbols-outlined text-[14px]">block</span>
                    <span>{{ __('global.rooms.badge_restricted') }}</span>
                  </span>
                </div>

                <!-- Title & ID -->
                <h2 class="text-base font-bold text-slate-700 line-clamp-1" title="{{ $item->room_name }}">
                  {{ $item->room_name }}
                </h2>
                <p class="text-xs text-slate-400 mt-0.5 font-mono uppercase">{{ $item->room_id_display }}</p>

                <!-- Warning Notice Banner Inside Card -->
                <div class="mt-4 p-2.5 rounded-lg bg-slate-50 border border-slate-200/70 flex items-start gap-2 text-slate-600">
                  <span class="material-symbols-outlined text-slate-400 text-[18px] shrink-0 mt-0.5">info</span>
                  <span class="text-xs leading-tight text-slate-600">
                    {{ __('global.rooms.access_restricted_msg') }}
                  </span>
                </div>

                <!-- Metrics Matrix -->
                <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-4">
                  <div>
                    <span class="text-xs text-slate-400 block">{{ __('global.rooms.joined_date') }}</span>
                    <span class="text-xs text-slate-600 mt-0.5 block">{{ $item->joined_at_formatted }}</span>
                  </div>
                  <div>
                    <span class="text-xs text-slate-400 block">{{ __('global.rooms.total_spent') }}</span>
                    <span class="text-xs text-slate-600 mt-0.5 block tabular-nums">{{ $item->total_spent_formatted }}</span>
                  </div>
                </div>
              </div>

              <!-- Card Action Disabled CTA -->
              <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                <span class="text-xs text-slate-400 italic">{{ __('global.rooms.access_locked') }}</span>
                <button class="h-[36px] px-4 bg-slate-100 text-slate-400 cursor-not-allowed rounded-lg text-xs font-semibold border border-slate-200 flex items-center gap-1.5 opacity-80" disabled>
                  <span class="material-symbols-outlined text-[16px]">lock_outline</span>
                  <span>{{ __('global.rooms.badge_restricted') }}</span>
                </button>
              </div>
            </div>
          @endif
        @empty
          <!-- Empty State -->
          <div class="col-span-1 md:col-span-2 lg:col-span-3 py-16 px-6 text-center bg-white rounded-xl border border-slate-200">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#006948] flex items-center justify-center mx-auto mb-4">
              <span class="material-symbols-outlined text-[32px]">meeting_room</span>
            </div>
            <h3 class="text-base font-bold text-slate-900">{{ __('global.rooms.empty_title') }}</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
              @if(!empty($search))
                {{ __('global.rooms.empty_search_desc', ['search' => $search]) }}
              @else
                {{ __('global.rooms.empty_no_rooms_desc') }}
              @endif
            </p>
            @if(!empty($search) || $filter !== 'all')
              <div class="mt-4">
                <a href="{{ route('user.me.rooms') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors">
                  <span class="material-symbols-outlined text-[16px]">clear_all</span>
                  <span>{{ __('global.rooms.clear_filter_btn') }}</span>
                </a>
              </div>
            @endif
          </div>
        @endforelse
      </div>

      <!-- Pagination Controls -->
      @if($paginator->total() > 0)
        <div class="mt-8 pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="text-xs text-slate-500">
            {{ __('global.rooms.pagination_showing', ['first' => $paginator->firstItem() ?? 0, 'last' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
          </div>
          <div class="inline-flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if($paginator->onFirstPage())
              <button disabled class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-300 text-xs flex items-center gap-1 cursor-not-allowed">
                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                <span>{{ __('global.rooms.prev') }}</span>
              </button>
            @else
              <a href="{{ $paginator->previousPageUrl() }}" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-xs flex items-center gap-1 transition-colors">
                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                <span>{{ __('global.rooms.prev') }}</span>
              </a>
            @endif

            {{-- Page Elements --}}
            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
              @if($page == $paginator->currentPage())
                <span class="w-9 h-9 rounded-lg bg-[#006948] text-white font-semibold text-xs flex items-center justify-center shadow-2xs">{{ $page }}</span>
              @else
                <a href="{{ $url }}" class="w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium text-xs flex items-center justify-center transition-colors">{{ $page }}</a>
              @endif
            @endforeach

            {{-- Next Page Link --}}
            @if($paginator->hasMorePages())
              <a href="{{ $paginator->nextPageUrl() }}" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-xs flex items-center gap-1 transition-colors">
                <span>{{ __('global.rooms.next') }}</span>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
              </a>
            @else
              <button disabled class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-300 text-xs flex items-center gap-1 cursor-not-allowed">
                <span>{{ __('global.rooms.next') }}</span>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
              </button>
            @endif
          </div>
        </div>
      @endif
    </section>

    <!-- Join Room / Room URL Action Canvas -->
    <section class="mt-8 bg-white rounded-xl border border-slate-200 p-6 sm:p-8 shadow-xs">
      <div class="w-full">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-[#006948] shrink-0">
            <span class="material-symbols-outlined text-[28px]">link</span>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-bold text-slate-900">{{ __('global.rooms.join_title') }}</h3>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
              {{ __('global.rooms.join_desc') }}
            </p>

            <!-- Inline Input Form for Room URL -->
            <form method="POST" action="{{ route('user.me.rooms.join') }}" class="mt-5">
              @csrf
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1">
                  <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none" aria-hidden="true">
                    <span class="material-symbols-outlined text-[18px]">link</span>
                  </span>
                  <input name="room_url"
                         value="{{ old('room_url') }}"
                         class="w-full pl-9 pr-4 h-[38px] bg-white border @error('room_url') border-red-500 @else border-slate-200 @enderror rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#006948] focus:ring-1 focus:ring-[#006948] font-mono transition-all outline-none"
                         placeholder="{{ __('global.rooms.join_placeholder') }}"
                         type="url"
                         required>
                </div>
                <button type="submit" class="h-[38px] px-6 bg-[#006948] hover:bg-[#005137] text-white rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1.5 shadow-2xs whitespace-nowrap cursor-pointer">
                  <span class="material-symbols-outlined text-[18px]">login</span>
                  <span>{{ __('global.rooms.join_btn') }}</span>
                </button>
              </div>
              @error('room_url')
                <p class="text-xs text-red-600 mt-1.5 font-medium flex items-center gap-1">
                  <span class="material-symbols-outlined text-[14px]">error</span>
                  <span>{{ $message }}</span>
                </p>
              @enderror
            </form>

          </div>
        </div>
      </div>
    </section>
  </div>
</x-global.layout>
