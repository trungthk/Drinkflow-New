<x-room.layout
  :title="'DrinkFlow - ' . __('room.orders.order_details') . ' #' . $order->id . ' - ' . ($room->name ?? __('global.common.room'))"
  :room="$room"
  :room-user="request()->attributes->get('room_user')"
  :user="request()->attributes->get('global_user') ?? auth('web')->user()"
  :active-tab="'orders'"
  :breadcrumbs="[
      ['title' => __('room.orders.page_title'), 'url' => route('user.orders.index', $room->slug)],
      ['title' => '#' . $order->id, 'url' => '']
  ]"
>
  <main class="w-full max-w-3xl mx-auto space-y-6"
        data-order-status-container
        data-status-url="{{ route('user.orders.show', [$room, $order]) }}"
        data-initial-status="{{ $order->status->value }}"
        data-label-submitted="{{ __('room.orders.status_submitted', ['default' => 'Đã gửi']) }}"
        data-label-confirmed="{{ __('room.orders.status_confirmed', ['default' => 'Đã nhận']) }}"
        data-label-ordering="{{ __('room.orders.status_ordering', ['default' => 'Đang làm']) }}"
        data-label-ordered="{{ __('room.orders.status_ordered', ['default' => 'Đã xong']) }}"
        data-label-delivering="{{ __('room.orders.status_delivering', ['default' => 'Đang giao']) }}"
        data-label-completed="{{ __('room.orders.status_completed', ['default' => 'Hoàn tất']) }}"
        data-label-cancelled="{{ __('room.orders.status_cancelled', ['default' => 'Đã hủy']) }}"
        data-label-updated-prefix="{{ __('room.orders.updated_prefix', ['default' => 'Cập nhật lúc']) }}"
  >
    <!-- Back to Room Orders link -->
    <div>
      <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#006948] transition-colors" href="{{ route('user.orders.index', $room->slug) }}">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>{{ __('room.orders.page_title') }}</span>
      </a>
    </div>

    <!-- Order Main Card -->
    <section class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-7 shadow-xs">
      <!-- Order Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-slate-100">
        <div>
          <span class="text-xs font-bold text-[#006948] uppercase tracking-wider">{{ $order->campaign->name ?? __('global.common.campaign') }}</span>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 mt-0.5">{{ __('room.orders.order_details') }} #{{ $order->id }}</h1>
        </div>
        <div>
          <span id="status-badge"
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-bold text-[#006948] border border-emerald-200/70 capitalize">
            {{ $order->status->value }}
          </span>
        </div>
      </div>

      <p id="status-updated" class="mt-3 text-xs text-slate-400 font-medium">{{ __('room.orders.auto_updating_status', ['default' => 'Đang cập nhật trạng thái tự động.']) }}</p>

      <!-- Status Progress Timeline -->
      <ol id="status-timeline" class="mt-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-xs">
        @foreach ([
            'submitted' => __('room.orders.status_submitted', ['default' => 'Đã gửi']),
            'confirmed' => __('room.orders.status_confirmed', ['default' => 'Đã nhận']),
            'ordering' => __('room.orders.status_ordering', ['default' => 'Đang làm']),
            'ordered' => __('room.orders.status_ordered', ['default' => 'Đã xong']),
            'delivering' => __('room.orders.status_delivering', ['default' => 'Đang giao']),
            'completed' => __('room.orders.status_completed', ['default' => 'Hoàn tất'])
        ] as $value => $label)
          <li data-status="{{ $value }}" class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-2.5 text-slate-500 transition-all text-center">
            <span class="block font-bold text-slate-900 text-xs truncate">{{ $label }}</span>
            <span class="mt-0.5 block text-[10px] uppercase tracking-wider text-slate-400 font-mono">{{ $value }}</span>
          </li>
        @endforeach
      </ol>

      <!-- Items List -->
      <div class="mt-6">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">{{ __('room.orders.items_list') }}</h2>
        <ul class="divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/30 overflow-hidden">
          @foreach ($order->items as $item)
            <li class="flex items-center justify-between p-3.5 sm:p-4 text-xs sm:text-sm">
              <div class="min-w-0 pr-3">
                <span class="font-bold text-slate-900">{{ $item->quantity }} × {{ $item->item_name }}</span>
                @if ($item->size_name)
                  <span class="text-xs text-slate-500 block sm:inline sm:ml-1">({{ $item->size_name }})</span>
                @endif
              </div>
              <span class="font-mono font-bold text-slate-900 shrink-0">{{ number_format($item->line_subtotal, 0, ',', '.') }} ₫</span>
            </li>
          @endforeach
        </ul>
      </div>

      <!-- Total Amount -->
      <div class="mt-6 flex items-center justify-between border-t border-slate-200/80 pt-4">
        <span class="text-sm sm:text-base font-bold text-slate-900">{{ __('room.orders.final_amount') }}</span>
        <span class="text-lg sm:text-xl font-bold font-mono text-[#006948]">{{ number_format($order->final_amount, 0, ',', '.') }} ₫</span>
      </div>

      <!-- Actions (Payment QR) -->
      @if ($order->payment_method === 'transfer')
        <div class="mt-6 pt-2">
          <a class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#006948] hover:bg-[#005137] px-5 py-3 text-center font-bold text-xs sm:text-sm text-white transition-colors shadow-sm"
             href="{{ route('user.orders.payment', [$room, $order]) }}">
            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
            <span>{{ __('room.orders.pay_now_vietqr') }}</span>
          </a>
        </div>
      @endif
    </section>
  </main>
</x-room.layout>
