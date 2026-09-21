<x-room.layout
  :title="'DrinkFlow - ' . __('room.orders.order_details') . ' #' . $order->code . ' - ' . ($room->name ?? __('global.common.room'))"
  :room="$room"
  :room-user="request()->attributes->get('room_user')"
  :user="request()->attributes->get('global_user') ?? auth('web')->user()"
  :active-tab="'orders'"
>
  <main class="w-full max-w-3xl mx-auto space-y-6"
        data-order-status-container
        data-status-url="{{ route('user.orders.show', [$room, $order]) }}"
        data-initial-status="{{ $order->status->value }}"
        data-label-submitted="{{ __('room.orders.status_submitted') }}"
        data-label-confirmed="{{ __('room.orders.status_confirmed') }}"
        data-label-ordering="{{ __('room.orders.status_ordering') }}"
        data-label-ordered="{{ __('room.orders.status_ordered') }}"
        data-label-delivering="{{ __('room.orders.status_delivering') }}"
        data-label-completed="{{ __('room.orders.status_completed') }}"
        data-label-cancelled="{{ __('room.orders.status_cancelled') }}"
        data-label-updated-prefix="{{ __('room.orders.updated_prefix') }}"
  >
    <!-- Back to Room Orders link -->
    <div>
      <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#006948] transition-colors" href="{{ route('user.orders.index', $room->slug) }}">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>{{ __('room.orders.page_title') }}</span>
      </a>
    </div>

    <!-- Order Main Card -->
    <section class="rounded-xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-2xs">
      <!-- Order Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3.5 border-b border-slate-100">
        <div>
          <span class="text-[11px] font-bold text-[#006948] uppercase tracking-wider">{{ $order->campaign->name ?? __('global.common.campaign') }}</span>
      <h1 class="text-sm sm:text-base font-bold text-slate-900 mt-0.5">{{ __('room.orders.order_details') }} #{{ $order->code }}</h1>
        </div>
        <div>
          <span id="status-badge"
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-[#006948] border border-emerald-200/70 capitalize">
            {{ $order->status->value }}
          </span>
        </div>
      </div>

      <p id="status-updated" class="mt-3 text-xs text-slate-400 font-medium">{{ __('room.orders.auto_updating_status') }}</p>

      <!-- Status Progress Timeline -->
      <ol id="status-timeline" class="mt-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-xs">
        @foreach ([
            'submitted' => __('room.orders.status_submitted'),
            'confirmed' => __('room.orders.status_confirmed'),
            'ordering' => __('room.orders.status_ordering'),
            'ordered' => __('room.orders.status_ordered'),
            'delivering' => __('room.orders.status_delivering'),
            'completed' => __('room.orders.status_completed')
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
              <span class="font-mono font-bold text-slate-900 shrink-0">{{ \App\Support\Helpers\FormatHelper::formatCurrency($item->line_subtotal) }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      <!-- Total Amount -->
      <div class="mt-6 flex items-center justify-between border-t border-slate-200/80 pt-4">
        <span class="text-sm sm:text-base font-bold text-slate-900">{{ __('room.orders.final_amount') }}</span>
        <span class="text-lg sm:text-xl font-bold font-mono text-[#006948]">{{ \App\Support\Helpers\FormatHelper::formatCurrency($order->final_amount) }}</span>
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
