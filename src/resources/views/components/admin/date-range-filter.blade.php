@props([
    'id' => 'admin-date-range-filter',
    'dateFrom' => request('date_from', ''),
    'dateTo' => request('date_to', ''),
    'formId' => null,
])

<div id="{{ $id }}" class="relative inline-block text-left" data-date-range-picker="true" @if($formId) data-form-id="{{ $formId }}" @endif>
    <input type="hidden" name="date_from" value="{{ $dateFrom }}" class="date-from-hidden" />
    <input type="hidden" name="date_to" value="{{ $dateTo }}" class="date-to-hidden" />

    <button
        type="button"
        class="date-range-toggle inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-colors shadow-sm"
        aria-haspopup="true"
        aria-expanded="false"
    >
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="date-range-label font-normal">
            @if(empty($dateFrom) && empty($dateTo))
                {{ __('admin.preset_all_time') }}
            @elseif(!empty($dateFrom) && !empty($dateTo) && $dateFrom === $dateTo && $dateFrom === date('Y-m-d'))
                {{ __('admin.preset_today') }}
            @elseif(!empty($dateFrom) && !empty($dateTo))
                {{ $dateFrom }} ~ {{ $dateTo }}
            @elseif(!empty($dateFrom))
                >= {{ $dateFrom }}
            @elseif(!empty($dateTo))
                <= {{ $dateTo }}
            @else
                {{ __('admin.date_range') }}
            @endif
        </span>
        <svg class="w-4 h-4 text-slate-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div class="date-range-dropdown hidden absolute right-0 z-30 mt-2 w-72 md:w-80 origin-top-right rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-slate-200 dark:border-slate-800 p-3.5 focus:outline-none animate-in fade-in zoom-in-95 duration-150">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 px-1">
            {{ __('admin.date_range') }}
        </div>

        <div class="grid grid-cols-2 gap-1.5 mb-3">
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="all">
                {{ __('admin.preset_all_time') }}
            </button>
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="today">
                {{ __('admin.preset_today') }}
            </button>
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="yesterday">
                {{ __('admin.preset_yesterday') }}
            </button>
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="last_7_days">
                {{ __('admin.preset_last_7_days') }}
            </button>
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="last_30_days">
                {{ __('admin.preset_last_30_days') }}
            </button>
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="this_month">
                {{ __('admin.preset_this_month') }}
            </button>
            <button type="button" class="preset-btn px-2.5 py-1.5 text-xs text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium transition-colors" data-preset="last_month">
                {{ __('admin.preset_last_month') }}
            </button>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-800 pt-3 space-y-2">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400 px-1">
                {{ __('admin.preset_custom') }}
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-0.5">{{ __('admin.start_date') }}</label>
                    <input type="date" class="date-from-input w-full px-2 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 focus:ring-1 focus:ring-primary-500" value="{{ $dateFrom }}" />
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-0.5">{{ __('admin.end_date') }}</label>
                    <input type="date" class="date-to-input w-full px-2 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 focus:ring-1 focus:ring-primary-500" value="{{ $dateTo }}" />
                </div>
            </div>
            <div class="pt-1 flex items-center justify-end gap-2">
                <button type="button" class="apply-btn w-full py-1.5 px-3 bg-primary-600 hover:bg-primary-500 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                    {{ __('admin.apply_date_range') }}
                </button>
            </div>
        </div>
    </div>
</div>
