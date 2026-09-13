<x-global.layout
  :title="'DrinkFlow - ' . __('global.orders.page_title')"
  :user="$user"
  :active-tab="'orders'"
  :breadcrumbs="$breadcrumbs"
  :unread-notifications-count="$unreadNotificationsCount"
  :notifications="$notifications"
>
  <div class="space-y-6"
    x-data="{
      showModal: false,
      activeOrder: null,
      openDetail(orderData) {
        this.activeOrder = orderData;
        this.showModal = true;
      },
      closeDetail() {
        this.showModal = false;
        this.activeOrder = null;
      },
      formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount || 0) + '{{ __('global.common.money_suffix') }}';
      }
    }"
    @keydown.escape.window="closeDetail()"
  >

    <!-- Breadcrumb & Title Area -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
          <a href="{{ route('user.me.dashboard') }}" class="hover:text-slate-700 transition-colors">{{ __('global.orders.breadcrumb_portal') }}</a>
          <span class="material-symbols-outlined text-[13px]">chevron_right</span>
          <a href="{{ route('user.me.dashboard') }}" class="hover:text-slate-700 transition-colors">{{ __('global.orders.breadcrumb_personal') }}</a>
          <span class="material-symbols-outlined text-[13px]">chevron_right</span>
          <span class="text-[#006948] font-medium">{{ __('global.orders.breadcrumb_history') }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ __('global.orders.title') }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ __('global.orders.subtitle') }}</p>
      </div>

      <div class="flex items-center gap-3">
        <a href="{{ route('user.me.payments') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#006948] hover:bg-[#005137] text-white rounded-xl text-sm font-semibold transition-colors shadow-xs">
          <span class="material-symbols-outlined text-[18px]">payments</span>
          <span>{{ __('global.orders.payments_cta') }}</span>
        </a>
      </div>
    </div>

    <!-- Filter Bar & Search Container -->
    <form method="GET" action="{{ route('user.me.orders') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
        <!-- Room Selector -->
        <div>
          <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ __('global.orders.label_room') }}</label>
          <div class="relative">
            <select name="room_id" onchange="this.form.submit()" class="w-full h-10 pl-3.5 pr-8 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-[#006948] focus:ring-1 focus:ring-[#006948] appearance-none cursor-pointer transition-colors">
              <option value="all">{{ __('global.orders.all_rooms_default') }}</option>
              @foreach($userRooms as $room)
                <option value="{{ $room->id }}" @selected((string)$selectedRoomId === (string)$room->id)>
                  {{ $room->name }}
                </option>
              @endforeach
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-2.5 text-slate-400 text-[18px] pointer-events-none">expand_more</span>
          </div>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ __('global.orders.label_status') }}</label>
          <div class="relative">
            <select name="status" onchange="this.form.submit()" class="w-full h-10 pl-3.5 pr-8 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-[#006948] focus:ring-1 focus:ring-[#006948] appearance-none cursor-pointer transition-colors">
              <option value="all" @selected($selectedStatus === 'all')>{{ __('global.orders.all_statuses') }}</option>
              <option value="paid" @selected($selectedStatus === 'paid')>{{ __('global.orders.status_paid') }}</option>
              <option value="unpaid" @selected($selectedStatus === 'unpaid')>{{ __('global.orders.status_unpaid') }}</option>
              <option value="cancelled" @selected($selectedStatus === 'cancelled')>{{ __('global.orders.status_cancelled') }}</option>
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-2.5 text-slate-400 text-[18px] pointer-events-none">expand_more</span>
          </div>
        </div>

        <!-- Time Range Filter -->
        <div>
          <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ __('global.orders.label_time') }}</label>
          <div class="relative">
            <select name="time_range" onchange="this.form.submit()" class="w-full h-10 pl-3.5 pr-8 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-[#006948] focus:ring-1 focus:ring-[#006948] appearance-none cursor-pointer transition-colors">
              <option value="this_month" @selected($selectedTime === 'this_month')>{{ __('global.orders.this_month', ['month' => now()->format('m/Y')]) }}</option>
              <option value="last_month" @selected($selectedTime === 'last_month')>{{ __('global.orders.last_month', ['month' => now()->subMonth()->format('m/Y')]) }}</option>
              <option value="last_3_months" @selected($selectedTime === 'last_3_months')>{{ __('global.orders.last_3_months') }}</option>
              <option value="all" @selected($selectedTime === 'all')>{{ __('global.orders.all_time') }}</option>
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-2.5 text-slate-400 text-[18px] pointer-events-none">calendar_today</span>
          </div>
        </div>
      </div>

      <!-- Search in filter -->
      <div class="lg:w-80 flex flex-col justify-end">
        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">{{ __('global.orders.quick_search') }}</label>
        <x-global.search-input
          name="q"
          :value="$search"
          :placeholder="__('global.search.order_placeholder')"
          input-class="w-full h-10 pl-10 pr-9 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 placeholder:text-slate-400 focus:bg-white focus:border-[#006948] focus:ring-1 focus:ring-[#006948] focus:outline-none transition-colors"
          :debounce="400"
        />
      </div>
    </form>

    <!-- Quick Stats Metrics Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Metric 1: Total Orders -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ __('global.orders.metric_month_orders') }}</p>
        <div class="flex items-baseline gap-2 mt-1.5">
          <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ $totalOrdersCount }} {{ __('global.common.orders_count') }}</span>
          @if($ordersDiff >= 0)
            <span class="text-xs text-emerald-600 font-medium flex items-center">
              <span class="material-symbols-outlined text-[14px]">arrow_upward</span> {{ __('global.orders.metric_diff_plus', ['count' => $ordersDiff]) }}
            </span>
          @else
            <span class="text-xs text-slate-500 font-medium flex items-center">
              <span class="material-symbols-outlined text-[14px]">arrow_downward</span> {{ __('global.orders.metric_diff_minus', ['count' => $ordersDiff]) }}
            </span>
          @endif
        </div>
      </div>

      <!-- Metric 2: Total Spent -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ __('global.orders.metric_total_spent') }}</p>
        <div class="flex items-baseline gap-2 mt-1.5">
          <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ number_format($totalSpent, 0, ',', '.') }}{{ __('global.common.money_suffix') }}</span>
          <span class="text-xs text-slate-400">{{ __('global.orders.metric_this_month') }}</span>
        </div>
      </div>

      <!-- Metric 3: Company Sponsor -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ __('global.orders.metric_company_sponsor') }}</p>
        <div class="flex items-baseline gap-2 mt-1.5">
          <span class="text-2xl font-bold text-[#006948] tracking-tight">
            {{ $totalSponsor > 0 ? '-' : '' }}{{ number_format($totalSponsor, 0, ',', '.') }}{{ __('global.common.money_suffix') }}
          </span>
          <span class="text-xs text-emerald-600 font-medium bg-emerald-50 px-1.5 py-0.5 rounded">{{ __('global.orders.metric_benefit') }}</span>
        </div>
      </div>

      <!-- Metric 4: Pending Payments -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{{ __('global.orders.metric_pending_payments') }}</p>
        <div class="flex items-baseline gap-2 mt-1.5">
          <span class="text-2xl font-bold {{ $pendingPaymentAmount > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">
            {{ number_format($pendingPaymentAmount, 0, ',', '.') }}{{ __('global.common.money_suffix') }}
          </span>
          @if($pendingPaymentCount > 0)
            <span class="text-xs bg-rose-50 text-rose-700 border border-rose-200 px-1.5 py-0.5 rounded font-medium">
              {{ $pendingPaymentCount }} {{ __('global.common.orders_count') }}
            </span>
          @else
            <span class="text-xs text-slate-400">{{ __('global.orders.metric_no_debt') }}</span>
          @endif
        </div>
      </div>
    </div>

    <!-- Orders Table Container -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
      <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
        <div class="flex items-center gap-2.5">
          <span class="font-bold text-slate-900 text-sm">{{ __('global.orders.orders_list_title') }}</span>
          <span class="bg-slate-200/70 text-slate-600 text-xs font-semibold px-2.5 py-0.5 rounded-full">
            {{ __('global.orders.orders_count_badge', ['showing' => $orders->count(), 'total' => $orders->total()]) }}
          </span>
        </div>
        <span class="text-xs text-slate-400 hidden sm:inline-flex items-center gap-1">
          <span class="material-symbols-outlined text-[14px]">sync</span>
          {{ __('global.orders.instant_sync') }}
        </span>
      </div>

      @if($orders->isEmpty())
        <!-- Empty State -->
        <div class="py-16 text-center px-4">
          <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-[32px]">receipt_long</span>
          </div>
          <h3 class="text-base font-bold text-slate-900 mb-1">{{ __('global.orders.empty_title') }}</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6">
            @if($search || $selectedRoomId || $selectedStatus !== 'all' || $selectedTime !== 'all')
              {{ __('global.orders.empty_filter_desc') }}
            @else
              {{ __('global.orders.empty_no_orders_desc') }}
            @endif
          </p>
          @if($search || $selectedRoomId || $selectedStatus !== 'all' || $selectedTime !== 'this_month')
            <a href="{{ route('user.me.orders') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
              <span class="material-symbols-outlined text-[16px]">refresh</span>
              {{ __('global.orders.reset_filters') }}
            </a>
          @else
            <a href="{{ route('user.me.rooms') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#006948] hover:bg-[#005137] text-white rounded-xl text-xs font-semibold transition-colors">
              <span class="material-symbols-outlined text-[16px]">meeting_room</span>
              {{ __('global.orders.explore_rooms') }}
            </a>
          @endif
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] tracking-wider uppercase text-slate-500 font-semibold">
                <th class="py-3 px-4">{{ __('global.orders.th_code_time') }}</th>
                <th class="py-3 px-4">{{ __('global.orders.th_room') }}</th>
                <th class="py-3 px-4">{{ __('global.orders.th_restaurant_items') }}</th>
                <th class="py-3 px-4 text-right">{{ __('global.orders.th_subtotal') }}</th>
                <th class="py-3 px-4 text-right">{{ __('global.orders.th_sponsor') }}</th>
                <th class="py-3 px-4 text-right">{{ __('global.orders.th_final') }}</th>
                <th class="py-3 px-4 text-center">{{ __('global.orders.th_status') }}</th>
                <th class="py-3 px-4 text-center">{{ __('global.orders.th_action') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              @foreach($orders as $order)
                @php
                  $orderStatus = is_object($order->status) ? $order->status->value : (string) $order->status;
                  $isPaid = in_array($orderStatus, ['paid', 'completed']);
                  $isUnpaid = in_array($orderStatus, ['submitted', 'confirmed', 'pending']);
                  $isCancelled = in_array($orderStatus, ['cancelled']);

                  // Items summary
                  $itemsSummary = $order->items->map(function ($item) {
                      $res = $item->item_name;
                      if (!empty($item->size_name)) $res .= ' size ' . $item->size_name;
                      return $res . ' x' . $item->quantity;
                  })->join(', ');

                  // Detail JSON for modal
                  $itemsList = $order->items->isNotEmpty()
                      ? $order->items->map(function ($item) {
                          $toppings = $item->toppings->pluck('topping_name')->join(', ');
                          $notes = [];
                          if ($item->ice_percent) $notes[] = $item->ice_percent . '% ' . __('global.common.items_count');
                          if ($item->sugar_percent) $notes[] = $item->sugar_percent . '%';
                          if ($toppings) $notes[] = '+' . $toppings;
                          if ($item->note) $notes[] = $item->note;
                          return [
                              'name' => $item->item_name,
                              'size' => $item->size_name ?: __('global.orders.standard_size'),
                              'notes' => implode(', ', $notes) ?: __('global.orders.default_notes'),
                              'quantity' => $item->quantity,
                              'unit_price' => (int) $item->unit_price,
                              'line_subtotal' => (int) ($item->line_subtotal ?: ($item->unit_price * $item->quantity)),
                          ];
                      })->values()->all()
                      : [[
                          'name' => $order->campaign?->name ?? 'Drink / Food',
                          'size' => __('global.orders.standard_size'),
                          'notes' => $order->note ?: __('global.orders.default_notes'),
                          'quantity' => 1,
                          'unit_price' => (int) ($order->subtotal ?: $order->final_amount),
                          'line_subtotal' => (int) ($order->subtotal ?: $order->final_amount),
                      ]];

                  $orderJson = [
                      'id' => $order->id,
                      'code' => '#ORD-' . $order->id,
                      'created_at' => $order->created_at ? $order->created_at->format('d/m/Y H:i') : '',
                      'room_name' => $order->room?->name ?? '',
                      'restaurant' => $order->campaign?->restaurant ?? ($order->campaign?->name ?? ''),
                      'host_name' => $order->campaign?->sponsor_name ?? 'Host',
                      'subtotal' => (int) ($order->subtotal ?: $order->final_amount),
                      'delivery_amount' => (int) $order->delivery_amount,
                      'discount_amount' => (int) $order->discount_amount,
                      'sponsor_amount' => (int) $order->sponsor_amount,
                      'final_amount' => (int) $order->final_amount,
                      'status' => $orderStatus,
                      'is_paid' => $isPaid,
                      'is_unpaid' => $isUnpaid,
                      'items' => $itemsList,
                  ];
                @endphp

                <tr class="hover:bg-slate-50/70 transition-colors {{ $loop->first ? 'bg-emerald-50/20' : '' }}">
                  <!-- Order Code & Time -->
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-1 font-bold text-[#006948] font-mono">
                      <span class="material-symbols-outlined text-[15px]">pin</span>
                      #ORD-{{ $order->id }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                      {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}
                    </div>
                  </td>

                  <!-- Room -->
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-medium">
                      <span class="w-1.5 h-1.5 rounded-full bg-[#006948]"></span>
                      {{ $order->room?->name ?? 'Room' }}
                    </span>
                  </td>

                  <!-- Restaurant & Items summary -->
                  <td class="py-3 px-4 max-w-xs">
                    <div class="font-semibold text-slate-900 truncate">
                      {{ $order->campaign?->restaurant ?? ($order->campaign?->name ?? 'Store') }}
                    </div>
                    <div class="text-[11px] text-slate-500 truncate mt-0.5" title="{{ $itemsSummary }}">
                      {{ $itemsSummary ?: 'Drink Order' }}
                    </div>
                  </td>

                  <!-- Subtotal -->
                  <td class="py-3 px-4 text-right font-mono text-slate-700">
                    {{ number_format($order->subtotal ?: $order->final_amount, 0, ',', '.') }}{{ __('global.common.money_suffix') }}
                  </td>

                  <!-- Sponsor -->
                  <td class="py-3 px-4 text-right font-mono text-[#006948] font-medium">
                    {{ $order->sponsor_amount ? '-' . number_format($order->sponsor_amount, 0, ',', '.') . __('global.common.money_suffix') : '0' . __('global.common.money_suffix') }}
                  </td>

                  <!-- Final Amount -->
                  <td class="py-3 px-4 text-right font-mono font-bold {{ $isUnpaid ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ number_format($order->final_amount, 0, ',', '.') }}{{ __('global.common.money_suffix') }}
                  </td>

                  <!-- Status Badge -->
                  <td class="py-3 px-4 text-center">
                    @if($isPaid)
                      <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="material-symbols-outlined text-[13px]">check_circle</span>
                        {{ __('global.orders.status_badge_paid') }}
                      </span>
                    @elseif($isUnpaid)
                      <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        {{ __('global.orders.status_badge_unpaid') }}
                      </span>
                    @elseif($isCancelled)
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        {{ __('global.orders.status_badge_cancelled') }}
                      </span>
                    @else
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                        {{ ucfirst($orderStatus) }}
                      </span>
                    @endif
                  </td>

                  <!-- Action -->
                  <td class="py-3 px-4 text-center">
                    <button
                      type="button"
                      @click="openDetail(@js($orderJson))"
                      class="inline-flex items-center gap-1 px-3 py-1 bg-white border border-slate-200 hover:border-[#006948] hover:text-[#006948] rounded-lg text-xs font-semibold text-slate-700 transition-colors shadow-2xs cursor-pointer"
                    >
                      <span>{{ __('global.orders.view_details') }}</span>
                      <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <!-- Pagination bar -->
        <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="text-xs text-slate-500">
            {{ __('global.orders.pagination_summary', ['first' => $orders->firstItem() ?? 0, 'last' => $orders->lastItem() ?? 0, 'total' => $orders->total()]) }}
          </div>
          <div>
            {{ $orders->links() }}
          </div>
        </div>
      @endif
    </div>

    <!-- DETAIL MODAL: Chi tiết đơn hàng -->
    <div
      x-show="showModal"
      x-cloak
      id="orderDetailModal"
      style="display: none;"
      class="fixed inset-0 z-50 overflow-y-auto items-center justify-center p-4 sm:p-6"
      :class="{ 'flex': showModal, 'hidden': !showModal }"
      aria-modal="true"
      role="dialog"
    >
      <!-- Backdrop overlay -->
      <div
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
        @click="closeDetail()"
      ></div>

      <!-- Modal Card -->
      <div
        class="relative bg-white border border-slate-200 rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden z-10 flex flex-col my-8"
        x-show="showModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
      >
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-start justify-between gap-4 bg-slate-50/50">
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
              <h2 class="text-xl font-bold text-slate-900 font-mono tracking-tight" x-text="'{{ __('global.orders.modal_title', ['code' => '']) }}' + (activeOrder?.code || '')"></h2>
              <template x-if="activeOrder?.is_paid">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span class="material-symbols-outlined text-[13px]">verified</span>
                  {{ __('global.orders.status_badge_paid') }}
                </span>
              </template>
              <template x-if="activeOrder?.is_unpaid">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                  {{ __('global.orders.status_badge_unpaid') }}
                </span>
              </template>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">schedule</span>
                <span x-text="activeOrder?.created_at"></span>
              </span>
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">meeting_room</span>
                <span x-text="activeOrder?.room_name"></span>
              </span>
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">storefront</span>
                <span class="font-medium text-slate-800" x-text="activeOrder?.restaurant"></span>
              </span>
            </div>
          </div>
          <button
            type="button"
            @click="closeDetail()"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
            title="{{ __('global.common.close') }}"
          >
            <span class="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 max-h-[calc(85vh-160px)]">
          <!-- Itemized Breakdown Table -->
          <div>
            <div class="flex items-center justify-between mb-2.5">
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[#006948] text-[18px]">lunch_dining</span>
                {{ __('global.orders.items_list_title') }}
              </h3>
              <span class="text-xs text-slate-400" x-text="(activeOrder?.items?.length || 0) + ' {{ __('global.common.items_count') }}'"></span>
            </div>
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] tracking-wider uppercase text-slate-500 font-semibold">
                    <th class="py-2.5 px-3">{{ __('global.orders.item_name_header') }}</th>
                    <th class="py-2.5 px-3">{{ __('global.orders.options_notes_header') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ __('global.orders.quantity_header') }}</th>
                    <th class="py-2.5 px-3 text-right">{{ __('global.orders.unit_price_header') }}</th>
                    <th class="py-2.5 px-3 text-right">{{ __('global.orders.line_total_header') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <template x-for="(item, idx) in (activeOrder?.items || [])" :key="idx">
                    <tr>
                      <td class="py-3 px-3">
                        <div class="font-semibold text-slate-900" x-text="item.name"></div>
                        <span class="text-[11px] text-slate-400" x-text="'Size ' + item.size"></span>
                      </td>
                      <td class="py-3 px-3 text-slate-600">
                        <span class="bg-slate-50 border border-slate-200 rounded px-2 py-0.5 text-[11px]" x-text="item.notes || '{{ __('global.orders.default_notes') }}'"></span>
                      </td>
                      <td class="py-3 px-3 text-center font-mono font-semibold" x-text="'x' + item.quantity"></td>
                      <td class="py-3 px-3 text-right font-mono text-slate-500" x-text="formatMoney(item.unit_price)"></td>
                      <td class="py-3 px-3 text-right font-mono font-bold text-slate-900" x-text="formatMoney(item.line_subtotal)"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Financial Calculation Box -->
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <h4 class="text-xs font-bold text-slate-900 mb-3 flex items-center justify-between">
              <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[17px] text-[#006948]">account_balance_wallet</span>
                {{ __('global.orders.financial_calc_title') }}
              </span>
              <span class="text-[11px] text-[#006948] font-semibold">{{ __('global.orders.auto_reconciled') }}</span>
            </h4>
            <div class="space-y-2 text-xs divide-y divide-slate-200/60">
              <div class="flex items-center justify-between pt-1">
                <span class="text-slate-600">{{ __('global.orders.subtotal_label') }}</span>
                <span class="font-mono font-medium text-slate-900" x-text="formatMoney(activeOrder?.subtotal)"></span>
              </div>
              <template x-if="activeOrder?.delivery_amount > 0">
                <div class="flex items-center justify-between pt-2">
                  <span class="text-slate-600">{{ __('global.orders.delivery_label') }}</span>
                  <span class="font-mono font-medium text-slate-900" x-text="'+' + formatMoney(activeOrder?.delivery_amount)"></span>
                </div>
              </template>
              <template x-if="activeOrder?.sponsor_amount > 0">
                <div class="flex items-center justify-between pt-2">
                  <span class="text-emerald-700 font-medium">{{ __('global.orders.sponsor_label') }}</span>
                  <span class="font-mono font-semibold text-emerald-700" x-text="'-' + formatMoney(activeOrder?.sponsor_amount)"></span>
                </div>
              </template>
              <div class="flex items-center justify-between pt-2 text-sm">
                <span class="font-bold text-slate-900">{{ __('global.orders.final_amount_label') }}</span>
                <span class="font-mono font-bold text-base" :class="activeOrder?.is_unpaid ? 'text-rose-600' : 'text-slate-900'" x-text="formatMoney(activeOrder?.final_amount)"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
          <div class="text-xs text-slate-500">
            <template x-if="activeOrder?.is_unpaid">
              <span class="text-rose-600 font-medium flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">warning</span>
                {{ __('global.orders.unpaid_warning') }}
              </span>
            </template>
            <template x-if="activeOrder?.is_paid">
              <span class="text-emerald-700 font-medium flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">check_circle</span>
                {{ __('global.orders.paid_success') }}
              </span>
            </template>
          </div>

          <div class="flex items-center gap-2.5">
            <button
              type="button"
              @click="closeDetail()"
              class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
            >
              {{ __('global.common.close') }}
            </button>
            <template x-if="activeOrder?.is_unpaid">
              <a
                href="{{ route('user.me.payments') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#006948] hover:bg-[#005137] text-white rounded-xl text-xs font-semibold transition-colors shadow-xs"
              >
                <span class="material-symbols-outlined text-[16px]">qr_code_scanner</span>
                {{ __('global.orders.vietqr_pay_btn') }}
              </a>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-global.layout>
