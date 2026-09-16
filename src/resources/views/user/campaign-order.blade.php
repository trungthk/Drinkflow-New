<x-room.layout
  :title="'DrinkFlow - ' . ($campaign->name ?? 'Campaign') . ' - ' . ($room->name ?? 'Room')"
  :room="$room"
  :room-user="request()->attributes->get('room_user')"
  :user="request()->attributes->get('global_user') ?? auth('web')->user()"
  :active-tab="'campaigns'"
  :breadcrumbs="[
      ['title' => __('room.campaign.page_title'), 'url' => route('user.campaigns.index', $room->slug)],
      ['title' => $campaign->name ?? 'Campaign', 'url' => '']
  ]"
>
  <main class="w-full max-w-3xl mx-auto space-y-6"
        data-campaign-order-container
        data-detail-url="{{ route('user.campaigns.show', [$room, $campaign]) }}"
        data-order-url="{{ route('user.orders.store', [$room, $campaign]) }}"
        data-room-slug="{{ $room->slug }}"
        data-msg-load-error="{{ __('room.campaign.menu_load_error', ['default' => 'Không thể tải menu.']) }}"
        data-msg-empty-menu="{{ __('room.campaign.menu_empty', ['default' => 'Menu đang trống.']) }}"
        data-msg-select-required="{{ __('room.campaign.select_item_required', ['default' => 'Hãy chọn ít nhất một món.']) }}"
        data-msg-order-success="{{ __('room.campaign.order_success', ['default' => 'Đặt món thành công!']) }}"
        data-msg-view-order="{{ __('room.orders.order_details', ['default' => 'Xem chi tiết đơn']) }}"
        data-msg-error-generic="{{ __('room.campaign.error_generic', ['default' => 'Không thể tạo đơn. Vui lòng thử lại.']) }}"
  >
    <!-- Back to Campaigns -->
    <div>
      <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#006948] transition-colors" href="{{ route('user.campaigns.index', $room->slug) }}">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>{{ __('room.campaign.page_title') }}</span>
      </a>
    </div>

    <!-- Header Section -->
    <header class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs">
      <div class="flex items-start justify-between gap-4">
        <div>
          <span class="text-xs font-bold text-[#006948] uppercase tracking-wider">{{ $campaign->restaurant ?? 'Restaurant' }}</span>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">{{ $campaign->name }}</h1>
          <p class="mt-1 text-xs sm:text-sm text-slate-500">{{ __('room.campaign.order_instruction', ['default' => 'Chọn món và gửi đơn. Giá sẽ được kiểm tra lại trên máy chủ.']) }}</p>
        </div>
      </div>
    </header>

    <!-- Order Form Section -->
    <form id="order-form" class="space-y-6">
      <!-- Menu Item Grid Container -->
      <div class="space-y-3">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('room.campaign.filter_all') }}</h2>
        <div id="items" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <p class="text-xs text-slate-400 py-4">{{ __('room.campaign.loading_menu', ['default' => 'Đang tải menu…']) }}</p>
        </div>
      </div>

      <!-- Payment & Note Details Card -->
      <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider" for="payment">
            {{ __('room.campaign.policy_payment_title') }}
          </label>
          <select id="payment" class="mt-1.5 w-full rounded-xl border border-slate-200/80 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all">
            <option value="transfer">{{ __('room.campaign.payment_transfer') }}</option>
            <option value="cash">{{ __('room.campaign.payment_cash') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider" for="note">
            {{ __('room.campaign.note_label') }}
          </label>
          <textarea id="note" rows="2" class="mt-1.5 w-full rounded-xl border border-slate-200/80 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all"
                    maxlength="1000"
                    placeholder="{{ __('room.campaign.note_placeholder') }}"></textarea>
        </div>

        <button class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#006948] hover:bg-[#005137] px-5 py-3.5 font-bold text-xs sm:text-sm text-white transition-colors shadow-sm cursor-pointer"
                type="submit">
          <span class="material-symbols-outlined text-[18px]">send</span>
          <span>{{ __('room.campaign.add_to_order') }}</span>
        </button>

        <p id="error" class="hidden text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl p-3"></p>
      </div>
    </form>

    <!-- Success Feedback Container -->
    <section id="success" class="hidden rounded-2xl bg-emerald-50 border border-emerald-200/80 p-5 sm:p-6 text-emerald-900"></section>
  </main>
</x-room.layout>
