{{-- Desktop notification on/off toggle, shared by the /me and room headers.
     Behaviour (browser permission request, remembered choice, icon/colour per state) lives in
     resources/js/global/desktop-notification.js. Hidden until JS confirms the browser supports notifications. --}}
<button type="button" style="display: none" data-desktop-notify-toggle aria-pressed="false"
  data-state-classes="{{ json_encode([
      'on' => 'border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100',
      'off' => 'border-slate-200 bg-white text-slate-400 hover:bg-slate-50 hover:text-slate-600',
      'blocked' => 'border-rose-200 bg-rose-50 text-rose-500 hover:bg-rose-100',
  ]) }}"
  data-i18n="{{ json_encode([
      'enable' => __('global.header.desktop_notify_enable'),
      'disable' => __('global.header.desktop_notify_disable'),
      'blocked' => __('global.header.desktop_notify_blocked'),
      'enabled_toast' => __('global.header.desktop_notify_enabled_toast'),
      'disabled_toast' => __('global.header.desktop_notify_disabled_toast'),
      'blocked_toast' => __('global.header.desktop_notify_blocked_toast'),
  ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
  title="{{ __('global.header.desktop_notify_enable') }}" aria-label="{{ __('global.header.desktop_notify_enable') }}"
  class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-lg sm:rounded-xl border border-slate-200 bg-white text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors shadow-2xs cursor-pointer shrink-0">
  <span class="material-symbols-outlined text-[17px] sm:text-[19px]" data-desktop-notify-icon aria-hidden="true">notifications_off</span>
</button>
