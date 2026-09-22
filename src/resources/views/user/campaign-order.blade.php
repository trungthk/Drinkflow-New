<x-room.layout
  :title="'DrinkFlow - ' . ($campaign->name ?? __('global.common.campaign')) . ' - ' . ($room->name ?? __('global.common.room'))"
  :room="$room"
  :room-user="request()->attributes->get('room_user')"
  :user="request()->attributes->get('global_user') ?? auth('web')->user()"
  :active-tab="'campaigns'"
>
  <main class="w-full max-w-3xl mx-auto space-y-6"
        data-campaign-order-container
        data-detail-url="{{ route('user.campaigns.show', [$room, $campaign]) }}"
        data-order-url="{{ route('user.orders.store', [$room, $campaign]) }}"
        data-room-slug="{{ $room->slug }}"
        data-msg-load-error="{{ __('room.campaign.menu_load_error') }}"
        data-msg-empty-menu="{{ __('room.campaign.menu_empty') }}"
        data-msg-select-required="{{ __('room.campaign.select_item_required') }}"
        data-msg-order-success="{{ __('room.campaign.order_success') }}"
        data-msg-view-order="{{ __('room.orders.order_details') }}"
        data-msg-error-generic="{{ __('room.campaign.error_generic') }}"
        data-msg-quantity-label="{{ __('room.campaign.order_quantity_aria') }}"
        data-msg-self-paid="{{ __('room.campaign.self_paid_label') }}"
        data-msg-order-number="{{ __('room.campaign.order_number') }}"
        data-msg-view-order-number="{{ __('room.campaign.view_order_number') }}"
  >
    <!-- Back to Campaigns -->
    <div>
      <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#006948] transition-colors" href="{{ route('user.campaigns.index', $room->slug) }}">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>{{ __('room.campaign.page_title') }}</span>
      </a>
    </div>

    <!-- Header Section -->
    <header class="bg-white border border-slate-200/80 rounded-xl p-3.5 sm:p-4 shadow-2xs">
      <div class="flex items-start justify-between gap-3">
        <div>
          <span class="text-[11px] font-bold text-[#006948] uppercase tracking-wider">{{ $campaign->restaurant ?? __('room.campaign.restaurant') }}</span>
          <h1 class="text-sm sm:text-base font-bold text-slate-900 mt-0.5">{{ $campaign->name }}</h1>
          <p class="mt-0.5 text-xs text-slate-500">{{ __('room.campaign.order_instruction') }}</p>
        </div>
      </div>
    </header>

    <!-- Order Form Section -->
    <form id="order-form" class="space-y-6">
      <!-- Menu Item Grid Container -->
      <div class="space-y-3">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('room.campaign.filter_all') }}</h2>
        <div id="items" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <p class="text-xs text-slate-400 py-4">{{ __('room.campaign.loading_menu') }}</p>
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
                    placeholder="{{ __('room.campaign.note_placeholder') }}">{{ (request()->attributes->get('global_user') ?? auth('web')->user())?->default_order_note }}</textarea>
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
