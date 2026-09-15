<x-room.layout
  :title="'DrinkFlow - ' . __('room.campaign.page_title') . ' - ' . ($room->name ?? 'Room')"
  :room="$room"
  :room-user="$roomUser"
  :user="$user"
  :active-tab="'campaigns'"
  :active-campaign="$activeCampaign ? ['name' => $activeCampaign->name, 'time_remaining' => $campaignStats['time_remaining'] ?? '14:22'] : null"
  :unread-notifications-count="$unreadNotificationsCount ?? 0"
  :user-rooms="$userRooms ?? collect()"
>
  <div class="space-y-6" x-data="{
    searchQuery: '',
    selectedCategory: 'all',
    showCustomModal: false,
    selectedItem: null,
    selectedSize: null,
    selectedToppings: [],
    icePercent: 70,
    sugarPercent: 70,
    note: '',
    get calculatedPrice() {
      if (!this.selectedItem) return 0;
      let total = parseInt(this.selectedItem.base_price || 0);
      if (this.selectedSize && this.selectedSize.price_delta) {
        total += parseInt(this.selectedSize.price_delta);
      }
      if (this.selectedToppings && this.selectedToppings.length > 0) {
        this.selectedToppings.forEach(top => {
          total += parseInt(top.price || 0);
        });
      }
      return total;
    },
    openCustomize(item) {
      this.selectedItem = item;
      this.selectedSize = (item.sizes && item.sizes.length > 0) ? item.sizes[0] : null;
      this.selectedToppings = [];
      this.icePercent = 70;
      this.sugarPercent = 70;
      this.note = '';
      this.showCustomModal = true;
    },
    filterMatch(item) {
      if (this.selectedCategory !== 'all' && item.category !== this.selectedCategory) {
        return false;
      }
      if (this.searchQuery.trim() !== '') {
        const q = this.searchQuery.toLowerCase();
        const name = (item.name || '').toLowerCase();
        const desc = (item.description || '').toLowerCase();
        return name.includes(q) || desc.includes(q);
      }
      return true;
    }
  }">

    @if($activeCampaign)
      <!-- 1. Active Campaign Top Banner -->
      <section class="bg-white border border-slate-200/80 rounded-2xl shadow-xs p-6 relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-emerald-50 pointer-events-none blur-2xl"></div>

        <div class="relative z-10 space-y-5">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <!-- Restaurant Meta -->
            <div class="flex items-start sm:items-center gap-4">
              <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#006948] border border-emerald-100 flex items-center justify-center shrink-0 shadow-xs">
                <span class="material-symbols-outlined text-[32px]">local_cafe</span>
              </div>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wider border border-emerald-200">
                    {{ __('room.campaign.active_run_badge') }}
                  </span>
                  <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                    <span class="material-symbols-outlined text-[15px] text-amber-500">star</span>
                    <span class="font-bold text-slate-800">4.9</span> {{ __('room.campaign.reviews_count', ['count' => 120]) }}
                  </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mt-1">{{ $activeCampaign->name }}</h1>
                <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                  <span class="material-symbols-outlined text-[15px] text-[#006948]">storefront</span>
                  <span class="font-semibold text-slate-800">{{ $activeCampaign->restaurant }}</span>
                  <span class="text-slate-300">•</span>
                  <span>{{ __('room.dashboard.created_by', ['name' => $activeCampaign->creator?->name ?? 'Admin']) }}</span>
                </p>
              </div>
            </div>

            <!-- Countdown Timer Pill -->
            <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-1.5 bg-slate-50 border border-slate-200/80 p-3.5 rounded-xl">
              <div class="flex items-center gap-2 font-mono text-rose-700 text-xs font-bold">
                <span class="material-symbols-outlined text-[18px] animate-pulse">schedule</span>
                <span>{{ $campaignStats['time_remaining'] ?? '14:22' }}</span>
              </div>
              <span class="text-[11px] text-slate-400 font-medium">{{ __('room.campaign.auto_lock_notice') }}</span>
            </div>
          </div>

          <!-- Policy Badges Strip -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100">
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">savings</span>
              </div>
              <div>
                <span class="text-[11px] text-slate-400 block font-medium">{{ __('room.campaign.policy_sponsor_title') }}</span>
                <span class="text-xs font-bold text-slate-900">{{ __('room.campaign.policy_sponsor_val', ['amount' => '20.000đ']) }}</span>
              </div>
            </div>
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
              </div>
              <div>
                <span class="text-[11px] text-slate-400 block font-medium">{{ __('room.campaign.policy_shipping_title') }}</span>
                <span class="text-xs font-bold text-slate-900">{{ __('room.campaign.policy_shipping_val', ['amount' => '200k']) }}</span>
              </div>
            </div>
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span>
              </div>
              <div>
                <span class="text-[11px] text-slate-400 block font-medium">{{ __('room.campaign.policy_payment_title') }}</span>
                <span class="text-xs font-bold text-slate-900">{{ __('room.campaign.policy_payment_val') }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Active User Order Alert Banner if placed -->
      @if($activeUserOrder)
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs sm:text-sm font-medium flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
          <div class="flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-[#006948] flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[18px]">check_circle</span>
            </span>
            <span>
              {{ __('room.campaign.cart_summary_title') }}: <strong class="text-[#006948]">{{ $activeUserOrder->items->pluck('item_name')->join(', ') }}</strong> ({{ number_format($activeUserOrder->final_amount, 0, ',', '.') }}đ)
            </span>
          </div>
          <a href="{{ route('user.orders.index', $room->slug) }}" class="inline-flex items-center gap-1 font-bold text-[#006948] hover:underline shrink-0">
            <span>{{ __('room.orders.order_details') }}</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          </a>
        </div>
      @else
        <form data-decline-campaign action="{{ route('user.campaigns.decline', [$room, $activeCampaign]) }}" method="POST" class="flex justify-end">
          @csrf
          <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-rose-700 underline underline-offset-2 transition-colors">
            {{ __('room.campaign.decline') }}
          </button>
          <span data-decline-message class="hidden text-xs font-medium text-slate-500">{{ __('room.campaign.declined') }}</span>
        </form>
      @endif

      <!-- 2. Controls: Category Pills & Search Bar -->
      <section class="space-y-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <!-- Category Pills -->
          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
            <button type="button" 
                    @click="selectedCategory = 'all'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap cursor-pointer"
                    :class="selectedCategory === 'all' ? 'bg-[#006948] text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'">
              {{ __('room.campaign.filter_all') }}
            </button>
            @foreach($categories as $cat)
              <button type="button" 
                      @click="selectedCategory = '{{ $cat }}'"
                      class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap cursor-pointer"
                      :class="selectedCategory === '{{ $cat }}' ? 'bg-[#006948] text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'">
                {{ $cat }}
              </button>
            @endforeach
          </div>

          <!-- Search Bar with Clear Button -->
          <div class="relative w-full sm:w-72">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input x-model="searchQuery"
                   type="text" 
                   class="w-full pl-9 pr-8 h-10 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none transition-all"
                   placeholder="{{ __('room.campaign.search_placeholder') }}">
            <button x-show="searchQuery.length > 0" 
                    x-cloak 
                    type="button" 
                    @click="searchQuery = ''" 
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 p-0.5 cursor-pointer">
              <span class="material-symbols-outlined text-[16px]">cancel</span>
            </button>
          </div>
        </div>

        <!-- Menu Items Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          @forelse($activeCampaign->items as $item)
            <div x-show="filterMatch({{ json_encode($item) }})"
                 class="bg-white border border-slate-200/80 hover:border-emerald-300 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 shadow-xs hover:shadow-md hover:-translate-y-0.5">
              <div>
                <div class="w-full h-36 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-[#006948] overflow-hidden mb-3 relative">
                  <span class="material-symbols-outlined text-[44px]">local_cafe</span>
                  @if($item->category)
                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-xs text-[10px] font-semibold text-slate-600 border border-slate-200/60 shadow-2xs">
                      {{ $item->category }}
                    </span>
                  @endif
                </div>
                <h3 class="text-sm font-bold text-slate-900 leading-snug truncate">{{ $item->name }}</h3>
                <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">{{ $item->description ?? __('room.campaign.default_drink_desc') }}</p>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                <span class="text-sm font-bold font-mono text-slate-900">{{ number_format($item->base_price, 0, ',', '.') }}đ</span>
                <button type="button" 
                        @click="openCustomize({{ json_encode($item) }})"
                        class="px-3.5 h-8 bg-[#006948] hover:bg-[#005137] text-white rounded-xl text-xs font-semibold flex items-center gap-1 transition-colors shadow-2xs cursor-pointer">
                  <span class="material-symbols-outlined text-[15px]">add</span>
                  <span>{{ __('room.dashboard.select_drink') }}</span>
                </button>
              </div>
            </div>
          @empty
            <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-dashed border-slate-200">
              <span class="material-symbols-outlined text-[36px] text-slate-300 mb-2">restaurant_menu</span>
              <p class="text-xs font-semibold text-slate-700">{{ __('room.campaign.menu_empty') }}</p>
            </div>
          @endforelse
        </div>
      </section>

      <!-- 3. Modal Tùy chỉnh món (Item Customization Modal) -->
      <div x-show="showCustomModal" 
           x-cloak 
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div @click.outside="showCustomModal = false"
             class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto animate-fadeIn">
          <!-- Modal Header -->
          <div class="p-5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#006948] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">local_cafe</span>
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900" x-text="selectedItem?.name"></h3>
                <span class="text-xs font-mono font-semibold text-slate-500" x-text="new Intl.NumberFormat('vi-VN').format(calculatedPrice) + 'đ'"></span>
              </div>
            </div>
            <button type="button" @click="showCustomModal = false" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
              <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
          </div>

          <!-- Modal Body Form -->
          <form method="POST" :action="'/rooms/{{ $room->slug }}/campaigns/{{ $activeCampaign->id }}/orders'" class="p-5 sm:p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            @csrf
            <input type="hidden" name="campaign_item_id" :value="selectedItem?.id">
            <input type="hidden" name="campaign_item_size_id" :value="selectedSize?.id">
            <input type="hidden" name="ice_percent" :value="icePercent">
            <input type="hidden" name="sugar_percent" :value="sugarPercent">
            <input type="hidden" name="quantity" value="1">

            <!-- Size Options -->
            <template x-if="selectedItem?.sizes && selectedItem.sizes.length > 0">
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-700 block">{{ __('room.campaign.size_label') }}</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  <template x-for="size in selectedItem.sizes" :key="size.id">
                    <button type="button" 
                            @click="selectedSize = size"
                            class="p-2.5 rounded-xl border text-left transition-all cursor-pointer"
                            :class="selectedSize?.id === size.id ? 'border-[#006948] bg-emerald-50/50 text-[#006948] font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                      <span class="text-xs block" x-text="'Size ' + size.name"></span>
                      <span class="text-[11px] font-mono opacity-80" x-text="size.price_delta > 0 ? '+' + new Intl.NumberFormat('vi-VN').format(size.price_delta) + 'đ' : '{{ __('room.campaign.standard_size') }}'"></span>
                    </button>
                  </template>
                </div>
              </div>
            </template>

            <!-- Topping Checkboxes -->
            <template x-if="selectedItem?.toppings && selectedItem.toppings.length > 0">
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-700 block">{{ __('room.campaign.topping_label') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <template x-for="top in selectedItem.toppings" :key="top.id">
                    <label class="p-2.5 rounded-xl border border-slate-200 flex items-center justify-between gap-2 hover:bg-slate-50 cursor-pointer text-xs">
                      <div class="flex items-center gap-2">
                        <input type="checkbox" 
                               name="toppings[]" 
                               :value="top.id"
                               @change="if ($event.target.checked) { selectedToppings.push(top); } else { selectedToppings = selectedToppings.filter(t => t.id !== top.id); }"
                               class="rounded border-slate-300 text-[#006948] focus:ring-[#006948]">
                        <span x-text="top.name" class="font-medium text-slate-800"></span>
                      </div>
                      <span class="font-mono text-slate-500 text-[11px]" x-text="'+' + new Intl.NumberFormat('vi-VN').format(top.price) + 'đ'"></span>
                    </label>
                  </template>
                </div>
              </div>
            </template>

            <!-- Ice Level -->
            <div class="space-y-2">
              <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-700">
                <span>{{ __('room.campaign.ice_label') }}</span>
                <span class="font-mono text-[#006948]" x-text="icePercent + '%'"></span>
              </div>
              <div class="grid grid-cols-5 gap-1.5 text-center">
                <template x-for="ice in [0, 30, 50, 70, 100]" :key="ice">
                  <button type="button" 
                          @click="icePercent = ice"
                          class="py-1.5 rounded-lg border text-xs font-semibold transition-all cursor-pointer"
                          :class="icePercent === ice ? 'border-[#006948] bg-[#006948] text-white' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'"
                          x-text="ice + '%'"></button>
                </template>
              </div>
            </div>

            <!-- Sugar Level -->
            <div class="space-y-2">
              <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-700">
                <span>{{ __('room.campaign.sugar_label') }}</span>
                <span class="font-mono text-[#006948]" x-text="sugarPercent + '%'"></span>
              </div>
              <div class="grid grid-cols-5 gap-1.5 text-center">
                <template x-for="sugar in [0, 30, 50, 70, 100]" :key="sugar">
                  <button type="button" 
                          @click="sugarPercent = sugar"
                          class="py-1.5 rounded-lg border text-xs font-semibold transition-all cursor-pointer"
                          :class="sugarPercent === sugar ? 'border-[#006948] bg-[#006948] text-white' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'"
                          x-text="sugar + '%'"></button>
                </template>
              </div>
            </div>

            <!-- Special Note -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-700 block">{{ __('room.campaign.note_label') }}</label>
              <textarea name="note" 
                        x-model="note"
                        rows="2" 
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#006948] focus:ring-1 focus:ring-[#006948] outline-none"
                        placeholder="{{ __('room.campaign.note_placeholder') }}"></textarea>
            </div>

            <!-- Modal Footer CTA -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
              <div class="text-left">
                <span class="text-[11px] text-slate-400 block">{{ __('room.campaign.unit_price') }}</span>
                <span class="text-sm sm:text-base font-bold font-mono text-[#006948]" x-text="new Intl.NumberFormat('vi-VN').format(calculatedPrice) + 'đ'"></span>
              </div>
              <button type="submit" 
                      class="px-5 h-10 bg-[#006948] hover:bg-[#005137] text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-xs cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">shopping_bag</span>
                <span>{{ __('room.campaign.add_to_order', ['amount' => '']) }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

    @else
      <!-- Empty State when no campaign is open -->
      <section class="bg-white border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs">
        <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-400 border border-slate-200 mx-auto flex items-center justify-center mb-3">
          <span class="material-symbols-outlined text-[36px]">bedtime</span>
        </div>
        <h2 class="text-lg font-bold text-slate-900">{{ __('room.campaign.no_campaign_title') }}</h2>
        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
          {{ __('room.campaign.no_campaign_desc') }}
        </p>
      </section>
    @endif
  </div>
</x-room.layout>
