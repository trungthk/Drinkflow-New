<x-admin.layout :title="__('admin.room_settings_title')" active="settings" :room="$room">
    {{-- ── Header & Action Ribbon ────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-outline mb-1">
                <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-primary transition-colors">{{ __('admin.breadcrumb_admin') }}</a>
                <span>/</span>
                <span>{{ __('admin.breadcrumb_rooms') }}</span>
                <span>/</span>
                <span class="text-on-surface font-semibold">{{ $room->name }}</span>
                <span>/</span>
                <span class="text-primary font-bold">{{ __('admin.payments_settings') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.room_settings_title') }}</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.notification-channels.page', $room) }}" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant text-on-surface rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors no-underline">
                <span class="material-symbols-outlined text-[16px] text-primary">notifications</span>
                <span>{{ __('admin.webhook_channel_btn') }}</span>
            </a>
        </div>
    </div>

    {{-- ── Notice Notification Banner ─────────────────────────────────────── --}}
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        {{-- ══════════════════════════════════════════════════════════════════════
             CỘT TRÁI (50%): THÔNG TIN CHIẾN DỊCH
        ══════════════════════════════════════════════════════════════════════ --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[20px]">campaign</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-bold text-sm text-on-surface truncate">{{ __('admin.campaign_info_title') }}</h2>
                        <p class="text-[11px] text-outline truncate">{{ __('admin.campaign_info_desc') }}</p>
                    </div>
                </div>
                <button type="submit" form="room-settings-form" id="save-campaign-settings-btn" class="px-3.5 py-1.5 bg-primary hover:bg-primary/90 text-on-primary rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-all shrink-0">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>{{ __('admin.save_changes_btn') }}</span>
                </button>
            </div>

            <form id="room-settings-form" data-loading-form="true" class="space-y-5">
                {{-- 1. Tên Chiến Dịch Mặc Định --}}
                <div class="p-4 bg-surface-container-low/50 border border-outline-variant/60 rounded-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-blue-600">edit_note</span>
                            <h3 class="font-bold text-xs text-on-surface">{{ __('admin.default_campaign_title') }}</h3>
                        </div>
                        <span class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-700 text-[10px] font-bold rounded">{{ __('admin.dynamic_tags') }}</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-[11px] text-outline mb-1">{{ __('admin.campaign_syntax_template') }}:</label>
                        <input type="text" id="set-template" value="{{ $settings['campaign_title_template'] ?? '['.$room->name.'] Trà chiều & Cafe {date}' }}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded-lg text-xs font-bold text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">

                        <div class="flex flex-wrap items-center gap-1.5 pt-2">
                            <span class="text-[11px] text-outline">{{ __('admin.supported_variables') }}</span>
                            <button type="button" onclick="insertTag('{date}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{date}</button>
                            <button type="button" onclick="insertTag('{time}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{time}</button>
                            <button type="button" onclick="insertTag('{day_of_week}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{day_of_week}</button>
                            <button type="button" onclick="insertTag('{creator_name}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{creator_name}</button>
                        </div>
                    </div>
                </div>

                {{-- 2. Trần ngân sách tối đa cho mỗi sản phẩm --}}
                <div class="p-4 bg-surface-container-low/50 border border-outline-variant/60 rounded-xl space-y-2.5">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-amber-600">payments</span>
                        <h3 class="font-bold text-xs text-on-surface">{{ __('admin.max_product_budget_ceiling') }}</h3>
                    </div>

                    <div>
                        <label class="block font-semibold text-[11px] text-outline mb-1">{{ __('admin.product_budget_limit_hint') }}</label>
                        <div class="relative">
                            <input type="text" inputmode="numeric" id="set-max-budget" data-format-currency="true" value="{{ number_format((int)($settings['max_campaign_budget'] ?? 70000), 0, ',', '.') }}" class="w-full h-9 pl-3 pr-16 bg-surface border border-outline-variant rounded-lg font-mono font-bold text-xs text-primary focus:border-primary focus:ring-1 focus:ring-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-outline">{{ __('admin.vnd_unit') }}</span>
                        </div>
                    </div>
                </div>

                {{-- 3. Chính Sách Chi Tiêu & Hạn Mức Nợ --}}
                <div class="p-4 bg-surface-container-low/50 border border-outline-variant/60 rounded-xl space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-purple-600">gavel</span>
                        <h3 class="font-bold text-xs text-on-surface">{{ __('admin.spending_debt_policy_title') }}</h3>
                    </div>

                    <div>
                        <label class="block font-semibold text-[11px] text-outline mb-1">{{ __('admin.personal_debt_ceiling') }}:</label>
                        <div class="relative">
                            <input type="text" inputmode="numeric" id="set-debt-ceiling" data-format-currency="true" value="{{ number_format((int)($settings['personal_debt_ceiling'] ?? 150000), 0, ',', '.') }}" class="w-full h-9 pl-3 pr-16 bg-surface border border-outline-variant rounded-lg font-mono font-bold text-xs text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-outline">{{ __('admin.vnd_unit') }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-surface border border-outline-variant/60 rounded-lg flex items-center justify-between">
                        <label for="set-autolock-debt" class="font-semibold text-xs text-on-surface cursor-pointer select-none">{{ __('admin.auto_lock_on_debt_limit') }}</label>
                        <input type="checkbox" id="set-autolock-debt" {{ ($settings['auto_lock_on_debt_limit'] ?? true) ? 'checked' : '' }} class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                    </div>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════════
             CỘT PHẢI (50%): DANH SÁCH TÀI KHOẢN THANH TOÁN (GRID)
        ══════════════════════════════════════════════════════════════════════ --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">account_balance</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-sm text-on-surface">{{ __('admin.payment_accounts_title') }}</h2>
                        <p class="text-[11px] text-outline">{{ __('admin.payment_accounts_desc') }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    onclick="openCreateAccountModal()"
                    class="px-3 py-1.5 bg-primary hover:bg-primary/90 text-on-primary rounded-lg text-xs font-bold flex items-center gap-1 shadow-xs transition-colors"
                >
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>{{ __('admin.add_new_account') }}</span>
                </button>
            </div>

            {{-- Grid danh sách tài khoản --}}
            <div class="grid grid-cols-1 gap-3.5" id="accounts-container">
                @forelse($accounts as $acc)
                    @php
                        $accStatusVal = $acc->status instanceof \BackedEnum ? $acc->status->value : (string) ($acc->status ?? 'active');
                        $rawAccNumber = (string) $acc->getRawOriginal('account_number');
                        $bankDisplay = trim($acc->bank_code . ' - ' . ($acc->bank_name ?: $acc->bank_code));
                    @endphp
                    <div class="p-4 bg-surface-container-low/40 hover:bg-surface-container-low/80 border border-outline-variant/70 hover:border-primary/40 rounded-xl transition-all flex flex-col justify-between gap-3 group">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold font-mono text-xs shrink-0 border border-primary/20">
                                    {{ $acc->bank_code }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-sm text-on-surface font-mono tracking-wide">{{ $rawAccNumber }}</span>
                                        @if($acc->is_default)
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">{{ __('admin.default_badge') }}</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $accStatusVal === 'active' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                                            {{ __('admin.status_' . $accStatusVal) }}
                                        </span>
                                    </div>
                                    <div class="text-xs font-bold text-secondary uppercase mt-0.5 truncate">{{ $acc->account_name }}</div>
                                    {{-- Hiển thị: Mã bank - Tên bank --}}
                                    <div class="text-[11px] text-outline truncate font-medium mt-0.5">{{ $bankDisplay }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons Toolbar --}}
                        <div class="pt-2 border-t border-outline-variant/40 flex items-center justify-between gap-2">
                            {{-- View VietQR Code Button --}}
                            <button
                                type="button"
                                data-qr-btn
                                data-account-id="{{ $acc->id }}"
                                data-qr-url="{{ route('admin.payment-accounts.qr', [$room, $acc]) }}"
                                class="px-2.5 py-1 bg-surface-container hover:bg-primary hover:text-on-primary text-on-surface rounded-md text-xs font-semibold flex items-center gap-1 border border-outline-variant transition-colors"
                            >
                                <span class="material-symbols-outlined text-[15px] text-primary group-hover:text-inherit">qr_code_2</span>
                                <span>{{ __('admin.view_qr') }}</span>
                            </button>

                            <div class="flex items-center gap-1">
                                {{-- Edit Button --}}
                                <button
                                    type="button"
                                    onclick="openEditAccountModal({{ $acc->id }}, '{{ $acc->bank_code }}', @js($acc->bank_name), '{{ $rawAccNumber }}', @js($acc->account_name), {{ $acc->is_default ? 'true' : 'false' }})"
                                    class="group/edit relative p-1.5 text-secondary hover:text-primary rounded-md hover:bg-surface-container transition-colors"
                                    title="{{ __('admin.edit') }}"
                                    aria-label="{{ __('admin.edit') }}"
                                >
                                    <span class="material-symbols-outlined text-[17px]">edit</span>
                                    <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/edit:opacity-100 group-focus-visible/edit:opacity-100">{{ __('admin.edit') }}</span>
                                </button>
                                {{-- Delete Button --}}
                                <button
                                    type="button"
                                    onclick="openDeleteAccountModal({{ $acc->id }})"
                                    class="group/del relative p-1.5 text-secondary hover:text-rose-600 rounded-md hover:bg-surface-container transition-colors"
                                    title="{{ __('admin.delete') }}"
                                    aria-label="{{ __('admin.delete') }}"
                                >
                                    <span class="material-symbols-outlined text-[17px]">delete</span>
                                    <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/del:opacity-100 group-focus-visible/del:opacity-100">{{ __('admin.delete') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-outline border border-dashed border-outline-variant rounded-xl">
                        <span class="material-symbols-outlined text-4xl text-outline-variant">account_balance_wallet</span>
                        <p class="text-xs mt-1.5 font-medium">{{ __('admin.no_payment_accounts') }}</p>
                        <button
                            type="button"
                            onclick="openCreateAccountModal()"
                            class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary rounded-lg text-xs font-bold transition-colors"
                        >
                            <span class="material-symbols-outlined text-[15px]">add</span>
                            <span>{{ __('admin.add_new_account') }}</span>
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════════
         MODAL: Thêm / Chỉnh Sửa Tài Khoản Thanh Toán
    ══════════════════════════════════════════════════════════════════════════════ --}}
    <div id="payment-account-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-surface-container-lowest shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">add_card</span>
                    <h3 id="payment-modal-title" class="font-bold text-sm text-on-surface">{{ __('admin.add_new_account') }}</h3>
                </div>
                <button type="button" onclick="closeAccountModal()" class="p-1 rounded-md hover:bg-surface-container text-outline transition-colors">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form id="payment-account-form" class="p-5 space-y-4 text-xs">
                <input id="payment-account-id" type="hidden">

                {{-- Mã ngân hàng có chức năng search theo mã hoặc tên ngân hàng --}}
                <div>
                    <label class="block font-semibold text-on-surface mb-1">{{ __('admin.bank_code') }}:</label>
                    <select id="acc-bank-code" data-searchable="true" data-placeholder="{{ __('admin.search_bank_placeholder') }}" class="w-full h-10 px-3 bg-surface border border-outline-variant rounded-lg text-on-surface font-semibold focus:border-primary focus:ring-1 focus:ring-primary" required>
                        @foreach($banks ?? app(\App\Services\Common\BankService::class)->getAllBanks() as $bank)
                            <option value="{{ $bank['code'] }}" data-bank-name="{{ $bank['name'] }}">{{ $bank['code'] }} – {{ $bank['short_name'] }} ({{ $bank['name'] }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Số tài khoản chỉ cho phép nhập số 0-9 --}}
                <div>
                    <label class="block font-semibold text-on-surface mb-1">{{ __('admin.account_number_label') }}</label>
                    <input type="text" id="acc-number" inputmode="numeric" pattern="[0-9]*" maxlength="30" placeholder="0011004382918" class="w-full h-10 px-3 bg-surface border border-outline-variant rounded-lg font-mono font-bold text-on-surface focus:border-primary focus:ring-1 focus:ring-primary" required>
                </div>

                <div>
                    <label class="block font-semibold text-on-surface mb-1">{{ __('admin.account_holder_label') }}</label>
                    <input type="text" id="acc-name" placeholder="NGUYEN VAN A" class="w-full h-10 px-3 bg-surface border border-outline-variant rounded-lg uppercase font-bold text-on-surface focus:border-primary focus:ring-1 focus:ring-primary" required>
                </div>

                <div class="pt-1 flex items-center gap-2">
                    <input type="checkbox" id="acc-default" checked class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                    <label for="acc-default" class="text-on-surface font-semibold cursor-pointer select-none">{{ __('admin.set_as_default') }}</label>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-outline-variant/60">
                    <button type="button" onclick="closeAccountModal()" class="px-4 py-2 border border-outline-variant rounded-lg font-semibold text-on-surface hover:bg-surface-container transition-colors">
                        {{ __('admin.cancel') }}
                    </button>
                    <button id="payment-submit-btn" type="submit" class="px-5 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded-lg font-bold shadow-sm transition-colors flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>{{ __('admin.save_changes_btn') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════════
         MODAL: Xem Mã VietQR Code (Hiệu ứng Snake Border 4.8s)
    ══════════════════════════════════════════════════════════════════════════════ --}}
    <div id="payment-qr-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="relative w-full max-w-sm rounded-2xl bg-surface-container-lowest shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary">qr_code_2</span>
                    <h3 class="font-bold text-sm text-on-surface">{{ __('admin.view_qr') }}</h3>
                </div>
                <button type="button" data-close-qr class="p-1 rounded-md hover:bg-surface-container text-outline transition-colors">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <div id="qr-modal-body" class="flex flex-col items-center justify-center px-5 py-6 gap-4">
                {{-- Loading state --}}
                <div id="qr-loading" class="flex flex-col items-center gap-3 py-8">
                    <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
                    <p class="text-xs text-outline">{{ __('admin.loading') }}</p>
                </div>

                {{-- QR Content --}}
                <div id="qr-canvas-wrap" class="hidden flex-col items-center gap-4 w-full">
                    <div id="qr-bank-badge" class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold tracking-wider uppercase"></div>

                    {{-- QR Card + Snake Border SVG overlay --}}
                    <div id="qr-card-container" class="relative p-4">
                        <svg id="qr-snake-svg" class="absolute inset-0 w-full h-full pointer-events-none z-10" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                            <rect class="qr-snake-track" x="1" y="1" width="98" height="98" rx="6" ry="6" pathLength="100"
                                style="fill:none;stroke:#e2e8f0;stroke-width:2.5;stroke-linecap:round;"/>
                            <rect id="qr-snake-rect" class="qr-snake-anim" x="1" y="1" width="98" height="98" rx="6" ry="6" pathLength="100"
                                style="fill:none;stroke:#16a34a;stroke-width:3;stroke-linecap:round;stroke-dasharray:5 95;stroke-dashoffset:0;"/>
                        </svg>
                        <img id="qr-img" class="block rounded-lg relative z-0 w-[220px] h-[220px] object-contain bg-white mx-auto shadow-xs" alt="VietQR" loading="lazy">
                        <canvas id="qr-canvas" class="hidden rounded-lg relative z-0 mx-auto"></canvas>
                    </div>

                    <div id="qr-amount-label" class="hidden text-2xl font-bold text-primary tracking-tight"></div>

                    <div id="qr-info" class="text-center space-y-0.5">
                        <p id="qr-acc-name" class="text-sm font-bold text-on-surface uppercase tracking-wide"></p>
                        <p id="qr-acc-number" class="text-xs text-outline font-mono tracking-widest"></p>
                        <div id="qr-desc-wrap" class="hidden mt-2">
                            <p class="text-[10px] text-outline uppercase tracking-wider mb-1">{{ __('admin.transfer_content') }}</p>
                            <span id="qr-desc-value" class="text-xs font-bold font-mono bg-surface border border-outline-variant rounded px-2 py-1"></span>
                        </div>
                    </div>

                    <p class="text-[10px] text-outline text-center">{{ __('admin.qr_scan_note') }}</p>
                </div>

                {{-- Error state --}}
                <div id="qr-error" class="hidden flex-col items-center gap-3 py-8 text-center">
                    <span class="material-symbols-outlined text-4xl text-outline-variant">error</span>
                    <p class="text-xs text-outline">{{ __('admin.qr_error') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════════
         MODAL: Xác Nhận Xóa Tài Khoản
    ══════════════════════════════════════════════════════════════════════════════ --}}
    <div id="payment-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl bg-surface-container-lowest shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-error">delete_forever</span>
                <h3 class="font-bold text-sm text-on-surface">{{ __('admin.delete_account_title') }}</h3>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs text-outline leading-relaxed">{{ __('admin.delete_account_confirm') }}</p>
                <p class="mt-2 text-xs leading-relaxed text-warning">{{ __('admin.delete_account_live_campaign_hint') }}</p>
            </div>
            <div class="flex justify-end gap-2 px-5 py-4 border-t border-outline-variant">
                <button type="button" data-close-delete class="px-4 py-2 border border-outline-variant rounded-lg text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors">
                    {{ __('admin.cancel') }}
                </button>
                <button type="button" id="confirm-payment-delete" class="px-4 py-2 rounded-lg bg-error text-on-error text-xs font-bold hover:bg-error/90 transition-colors">
                    {{ __('admin.delete_confirm_btn') }}
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        @keyframes qr-snake-grow {
            0%        { stroke-dasharray:  5  95; }
            20%       { stroke-dasharray: 20  80; }
            45%       { stroke-dasharray: 50  50; }
            70%       { stroke-dasharray: 80  20; }
            90%, 100% { stroke-dasharray: 100   0; }
        }
        @keyframes qr-snake-travel {
            from { stroke-dashoffset: 0; }
            to   { stroke-dashoffset: -100; }
        }
        #qr-snake-rect.running {
            animation:
                qr-snake-grow   4.8s ease-in-out infinite,
                qr-snake-travel 4.8s linear infinite;
        }
        @media (prefers-reduced-motion: reduce) {
            #qr-snake-rect.running {
                animation: none;
                stroke-dasharray: 100 0;
            }
        }
    </style>
    <script>
    (function () {
        'use strict';

        const $ = id => document.getElementById(id);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const roomSlug = '{{ $room->slug }}';

        function showNotice(msg, type = 'success') {
            const el = $('notice');
            if (!el) return;
            el.textContent = msg;
            el.className = type === 'success'
                ? 'mb-4 rounded-xl px-4 py-3 text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200'
                : 'mb-4 rounded-xl px-4 py-3 text-xs font-medium bg-rose-50 text-rose-800 border border-rose-200';
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 4000);
        }

        function openModal(id) {
            const m = $(id);
            if (!m) return;
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeModal(id) {
            const m = $(id);
            if (!m) return;
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        /* ── Template Tag Inserter ────────────────────────────────────────── */
        window.insertTag = function (tag) {
            const input = $('set-template');
            if (input) {
                input.value += ' ' + tag;
                input.focus();
            }
        };

        /* ── Currency Formatter ───────────────────────────────────────────── */
        function formatNumberWithDots(input) {
            if (!input) return;
            const raw = String(input.value || '').replace(/\D/g, '');
            if (!raw) {
                input.value = '';
                return;
            }
            input.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        const budgetInput = $('set-max-budget');
        const debtInput = $('set-debt-ceiling');

        [budgetInput, debtInput].forEach(input => {
            if (input) {
                formatNumberWithDots(input);
                input.addEventListener('input', () => formatNumberWithDots(input));
                input.addEventListener('change', () => formatNumberWithDots(input));
            }
        });

        /* ── Numeric-Only Filter for Account Number (0-9) ─────────────────── */
        const accNumberInput = $('acc-number');
        if (accNumberInput) {
            accNumberInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });
            accNumberInput.addEventListener('paste', function (e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                this.value = paste.replace(/\D/g, '');
            });
        }

        /* ── Submit Campaign Settings Form ────────────────────────────────── */
        const settingsForm = $('room-settings-form');
        settingsForm?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const cleanNumber = (val) => {
                const raw = String(val || '').replace(/\D/g, '');
                return raw ? parseInt(raw, 10) : 0;
            };

            const payload = {
                campaign_title_template: $('set-template')?.value || '',
                max_campaign_budget: cleanNumber(budgetInput?.value),
                personal_debt_ceiling: cleanNumber(debtInput?.value),
                auto_lock_on_debt_limit: $('set-autolock-debt')?.checked ?? true
            };

            const submitBtn = $('save-campaign-settings-btn') || document.querySelector('button[type="submit"][form="room-settings-form"]');
            const originalSubmitContent = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span><span>{{ __('admin.loading') }}</span>';
            }

            try {
                const res = await fetch(`/admin/${roomSlug}/settings`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    showNotice('{{ __('admin.settings_saved_ok') }}');
                } else {
                    showNotice('{{ __('admin.error_generic') }}', 'error');
                }
            } catch (err) {
                console.error(err);
                showNotice('{{ __('admin.error_generic') }}', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    submitBtn.innerHTML = originalSubmitContent;
                }
            }
        });

        /* ── Payment Account Create / Edit Modals ─────────────────────────── */
        window.openCreateAccountModal = function () {
            $('payment-account-form')?.reset();
            $('payment-account-id').value = '';
            $('payment-modal-title').textContent = '{{ __('admin.add_new_account') }}';
            const bankSelect = $('acc-bank-code');
            if (bankSelect) {
                bankSelect.selectedIndex = 0;
                bankSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            openModal('payment-account-modal');
        };

        window.openEditAccountModal = function (id, bankCode, bankName, accountNumber, accountName, isDefault) {
            $('payment-account-id').value = id;
            const bankSelect = $('acc-bank-code');
            if (bankSelect) {
                bankSelect.value = bankCode;
                bankSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            $('acc-number').value = accountNumber;
            $('acc-name').value = accountName;
            $('acc-default').checked = isDefault;
            $('payment-modal-title').textContent = '{{ __('admin.edit_account') }}';
            openModal('payment-account-modal');
        };

        window.closeAccountModal = function () {
            closeModal('payment-account-modal');
        };

        /* Form submit: Create or Update Payment Account with Submit Loading */
        $('payment-account-form')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const accountId = $('payment-account-id').value;
            const bankSelect = $('acc-bank-code');
            const bankCode = bankSelect.value;
            const bankName = bankSelect.options[bankSelect.selectedIndex]?.dataset.bankName ?? bankCode;
            const body = JSON.stringify({
                bank_code: bankCode,
                bank_name: bankName,
                account_number: $('acc-number').value.trim(),
                account_name: $('acc-name').value.trim().toUpperCase(),
                is_default: $('acc-default').checked,
            });

            const url = `/admin/${roomSlug}/payment-accounts` + (accountId ? '/' + accountId : '');
            const method = accountId ? 'PATCH' : 'POST';

            const submitBtn = $('payment-submit-btn');
            const originalSubmitContent = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span><span>{{ __('admin.loading') }}</span>';
            }

            let isSuccess = false;
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const json = await res.json();
                if (!res.ok) {
                    const msgs = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? '{{ __('admin.error_generic') }}');
                    showNotice(msgs, 'error');
                    return;
                }
                isSuccess = true;
                closeAccountModal();
                showNotice(accountId ? '{{ __('admin.account_updated_ok') }}' : '{{ __('admin.account_created_ok') }}');
                setTimeout(() => location.reload(), 700);
            } catch {
                showNotice('{{ __('admin.error_generic') }}', 'error');
            } finally {
                if (submitBtn && !isSuccess) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    submitBtn.innerHTML = originalSubmitContent;
                }
            }
        });

        /* ── QR Modal (Snake Border 4.8s) ─────────────────────────────────── */
        function setQrState(state) {
            $('qr-loading').classList.toggle('hidden', state !== 'loading');
            $('qr-canvas-wrap').classList.toggle('hidden', state !== 'ready');
            $('qr-error').classList.toggle('hidden', state !== 'error');
            if (state === 'ready') {
                $('qr-canvas-wrap').classList.add('flex');
            }
        }

        function stopSnake() {
            $('qr-snake-rect')?.classList.remove('running');
        }
        function startSnake() {
            $('qr-snake-rect')?.classList.add('running');
        }

        function loadQrLibrary() {
            if (window.QRCode?.toCanvas) return Promise.resolve(window.QRCode);
            if (window.qrcode?.toCanvas) return Promise.resolve(window.qrcode);

            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js';
                script.onload = () => {
                    const library = window.QRCode?.toCanvas ? window.QRCode : window.qrcode;
                    resolve(library || null);
                };
                script.onerror = () => resolve(null);
                document.head.appendChild(script);
            });
        }

        async function openQrModal(accountId, qrUrl) {
            openModal('payment-qr-modal');
            setQrState('loading');
            stopSnake();

            try {
                const res = await fetch(qrUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const json = await res.json();
                const d = json.data;

                if (!d) throw new Error('no_data');

                const imgUrl = d.qr_url || `https://img.vietqr.io/image/${encodeURIComponent(d.bank_code)}-${encodeURIComponent(d.account_number)}-compact2.png?amount=${d.amount || 0}&addInfo=${encodeURIComponent(d.description || '')}&accountName=${encodeURIComponent(d.account_name || '')}`;

                const qrImg = $('qr-img');
                if (qrImg) {
                    qrImg.src = imgUrl;
                }

                $('qr-bank-badge').textContent = d.bank_name || d.bank_code;
                $('qr-acc-name').textContent = d.account_name || '';
                $('qr-acc-number').textContent = d.account_number || '';

                if (d.amount && d.amount > 0) {
                    $('qr-amount-label').textContent = new Intl.NumberFormat('vi-VN').format(d.amount) + ' ₫';
                    $('qr-amount-label').classList.remove('hidden');
                } else {
                    $('qr-amount-label').classList.add('hidden');
                }

                if (d.description) {
                    $('qr-desc-value').textContent = d.description;
                    $('qr-desc-wrap').classList.remove('hidden');
                } else {
                    $('qr-desc-wrap').classList.add('hidden');
                }

                setQrState('ready');
                startSnake();

                if (d.payload) {
                    loadQrLibrary().then((qrLibrary) => {
                        if (qrLibrary && $('qr-canvas')) {
                            const canvas = $('qr-canvas');
                            canvas.width = 220;
                            canvas.height = 220;
                            qrLibrary.toCanvas(canvas, d.payload, {
                                width: 220,
                                margin: 1,
                                errorCorrectionLevel: 'M',
                                color: { dark: '#000000', light: '#ffffff' },
                            }).catch(() => {});
                        }
                    }).catch(() => {});
                }
            } catch (err) {
                console.error('QR fetch error:', err);
                setQrState('error');
            }
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-qr-btn]');
            if (btn) {
                openQrModal(btn.dataset.accountId, btn.dataset.qrUrl);
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-close-qr]') || (e.target.id === 'payment-qr-modal')) {
                closeModal('payment-qr-modal');
                stopSnake();
            }
        });

        /* ── Delete Confirmation Modal ────────────────────────────────────── */
        let pendingDeleteId = null;

        window.openDeleteAccountModal = function (id) {
            pendingDeleteId = id;
            openModal('payment-delete-modal');
        };

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-close-delete]') || e.target.id === 'payment-delete-modal') {
                closeModal('payment-delete-modal');
                pendingDeleteId = null;
            }
        });

        $('confirm-payment-delete')?.addEventListener('click', async function () {
            if (!pendingDeleteId) return;
            const targetId = pendingDeleteId;
            closeModal('payment-delete-modal');
            pendingDeleteId = null;

            try {
                const res = await fetch(
                    `/admin/${roomSlug}/payment-accounts/${targetId}`,
                    { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const json = await res.json();
                if (json.data?.deleted) {
                    showNotice('{{ __('admin.account_deleted_ok') }}');
                    setTimeout(() => location.reload(), 700);
                } else {
                    showNotice(json.message ?? '{{ __('admin.error_generic') }}', 'error');
                }
            } catch {
                showNotice('{{ __('admin.error_generic') }}', 'error');
            }
        });

    }());
    </script>
    @endpush
</x-admin.layout>
