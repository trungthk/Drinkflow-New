<x-admin.layout :title="__('admin.vietqr_accounts_title')" active="payment_accounts" :room="$room">

    {{-- ── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-outline mb-1">
                <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-primary transition-colors">{{ __('admin.breadcrumb_admin') }}</a>
                <span>/</span>
                <span>{{ __('admin.breadcrumb_rooms') }}</span>
                <span>/</span>
                <span class="text-on-surface font-semibold">{{ $room->name }}</span>
                <span>/</span>
                <span class="text-primary font-bold">{{ __('admin.config_vietqr') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.vietqr_accounts_title') }}</h1>
        </div>
    </div>

    {{-- ── Notice banner ────────────────────────────────────────────────────────── --}}
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Left: Account list ───────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
                <div class="p-4 border-b border-outline-variant flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">account_balance</span>
                        <h2 class="font-bold text-sm text-on-surface">{{ __('admin.bank_accounts_list') }}</h2>
                    </div>
                    <span class="text-xs text-outline font-mono">{{ $accounts->count() }}</span>
                </div>

                <div class="divide-y divide-outline-variant/50">
                    @forelse($accounts as $acc)
                        @php
                            $accStatusVal = $acc->status instanceof \BackedEnum ? $acc->status->value : (string) ($acc->status ?? 'active');
                        @endphp
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-surface-container-low/40 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center font-bold font-mono text-sm shrink-0">
                                    {{ $acc->bank_code }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-sm text-on-surface font-mono">{{ $acc->account_number }}</span>
                                        @if($acc->is_default)
                                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">{{ __('admin.default_badge') }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $accStatusVal === 'active' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                                            {{ __('admin.status_' . $accStatusVal) }}
                                        </span>
                                    </div>
                                    <div class="text-xs font-semibold text-secondary uppercase mt-0.5">{{ $acc->account_name }}</div>
                                    <div class="text-[11px] text-outline mt-0.5">{{ $acc->bank_name ?: $acc->bank_code }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                {{-- View QR button — triggers AJAX to /qr endpoint then shows modal --}}
                                <button
                                    type="button"
                                    data-qr-btn
                                    data-account-id="{{ $acc->id }}"
                                    data-qr-url="{{ route('admin.payment-accounts.qr', [$room, $acc]) }}"
                                    class="px-2.5 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded text-xs font-semibold flex items-center gap-1 border border-outline-variant transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[14px] text-primary">qr_code_2</span>
                                    <span>{{ __('admin.view_qr') }}</span>
                                </button>
                                <button
                                    type="button"
                                    onclick="editPaymentAccount({{ $acc->id }}, '{{ $acc->bank_code }}', @js($acc->bank_name), '{{ $acc->account_number }}', @js($acc->account_name), {{ $acc->is_default ? 'true' : 'false' }})"
                                    class="p-1.5 text-secondary hover:text-primary rounded hover:bg-surface-container transition-colors"
                                    title="{{ __('admin.edit') }}"
                                >
                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                </button>
                                <button
                                    type="button"
                                    onclick="deleteAccount({{ $acc->id }})"
                                    class="p-1.5 text-secondary hover:text-rose-600 rounded hover:bg-surface-container transition-colors"
                                    title="{{ __('admin.delete') }}"
                                >
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-outline">
                            <span class="material-symbols-outlined text-4xl text-outline-variant">qr_code_2</span>
                            <p class="text-xs mt-1">{{ __('admin.no_payment_accounts') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Right: Add / Edit form ───────────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-outline-variant mb-4">
                    <span class="material-symbols-outlined text-[20px] text-primary">add_card</span>
                    <h2 id="payment-form-title" class="font-bold text-sm text-on-surface">{{ __('admin.add_new_account') }}</h2>
                </div>

                <form id="add-payment-form" data-loading-form="true" class="space-y-3 text-xs">
                    <input id="payment-account-id" type="hidden">

                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.bank_code') }}:</label>
                        <select id="acc-bank-code" data-searchable="true" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface font-semibold" required>
                            @foreach($banks ?? app(\App\Services\Common\BankService::class)->getAllBanks() as $bank)
                                <option value="{{ $bank['code'] }}" data-bank-name="{{ $bank['name'] }}">{{ $bank['code'] }} – {{ $bank['short_name'] }} ({{ $bank['name'] }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.account_number_label') }}</label>
                        <input type="text" id="acc-number" placeholder="0011004382918" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-on-surface" required>
                    </div>

                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.account_holder_label') }}</label>
                        <input type="text" id="acc-name" placeholder="NGUYEN VAN A" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded uppercase font-bold text-on-surface" required>
                    </div>

                    <div class="pt-1 flex items-center gap-2">
                        <input type="checkbox" id="acc-default" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                        <label for="acc-default" class="text-on-surface font-semibold cursor-pointer">{{ __('admin.set_as_default') }}</label>
                    </div>

                    <div class="pt-3 flex gap-2">
                        <button id="payment-submit" type="submit" class="flex-1 h-9 bg-primary hover:bg-primary/90 text-on-primary rounded font-bold transition-colors">
                            {{ __('admin.save_vietqr_account') }}
                        </button>
                        <button id="payment-cancel-edit" type="button" onclick="resetForm()" class="hidden h-9 px-3 border border-outline-variant rounded font-semibold text-on-surface hover:bg-surface-container transition-colors">
                            {{ __('admin.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════════
         MODAL: View VietQR Code
    ══════════════════════════════════════════════════════════════════════════════ --}}
    <div id="payment-qr-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="relative w-full max-w-sm rounded-2xl bg-surface-container-lowest shadow-2xl overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary">qr_code_2</span>
                    <h3 class="font-bold text-sm text-on-surface">{{ __('admin.view_qr') }}</h3>
                </div>
                <button type="button" data-close-qr class="p-1 rounded hover:bg-surface-container text-outline transition-colors">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            {{-- QR content area — will be populated by JS using <x-viet-qr-payment> --}}
            <div id="qr-modal-body" class="flex flex-col items-center justify-center px-5 py-6 gap-4">
                {{-- Loading state --}}
                <div id="qr-loading" class="flex flex-col items-center gap-3 py-8">
                    <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
                    <p class="text-xs text-outline">{{ __('admin.loading') }}</p>
                </div>

                {{-- QR rendered here by JS (canvas from qrcode.js library) --}}
                <div id="qr-canvas-wrap" class="hidden flex-col items-center gap-4 w-full">
                    {{-- Bank name badge --}}
                    <div id="qr-bank-badge" class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold tracking-wider uppercase"></div>

                    {{-- QR canvas + snake border card --}}
                    <div id="qr-card-container" class="relative p-4">
                        {{-- Snake border SVG overlay --}}
                        <svg id="qr-snake-svg" class="absolute inset-0 w-full h-full pointer-events-none z-10" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                            <rect class="qr-snake-track" x="1" y="1" width="98" height="98" rx="6" ry="6" pathLength="100"
                                style="fill:none;stroke:#e2e8f0;stroke-width:2.5;stroke-linecap:round;"/>
                            <rect id="qr-snake-rect" class="qr-snake-anim" x="1" y="1" width="98" height="98" rx="6" ry="6" pathLength="100"
                                style="fill:none;stroke:#16a34a;stroke-width:3;stroke-linecap:round;stroke-dasharray:5 95;stroke-dashoffset:0;"/>
                        </svg>
                        {{-- The actual QR canvas goes here --}}
                        <canvas id="qr-canvas" class="block rounded-lg relative z-0" style="display:block;"></canvas>
                    </div>

                    {{-- Amount --}}
                    <div id="qr-amount-label" class="hidden text-2xl font-bold text-primary tracking-tight"></div>

                    {{-- Account info --}}
                    <div id="qr-info" class="text-center space-y-0.5">
                        <p id="qr-acc-name" class="text-sm font-bold text-on-surface uppercase tracking-wide"></p>
                        <p id="qr-acc-number" class="text-xs text-outline font-mono tracking-widest"></p>
                        <div id="qr-desc-wrap" class="hidden mt-2">
                            <p class="text-[10px] text-outline uppercase tracking-wider mb-1">{{ __('admin.transfer_content') }}</p>
                            <span id="qr-desc-value" class="text-xs font-bold font-mono bg-surface border border-outline-variant rounded px-2 py-1"></span>
                        </div>
                    </div>

                    {{-- Note --}}
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
         MODAL: Confirm Delete
    ══════════════════════════════════════════════════════════════════════════════ --}}
    <div id="payment-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl bg-surface-container-lowest shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-error">delete_forever</span>
                <h3 class="font-bold text-sm text-on-surface">{{ __('admin.delete_account_title') }}</h3>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs text-outline leading-relaxed">{{ __('admin.delete_account_confirm') }}</p>
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
    {{-- qrcode.js (MIT) — lightweight, no dependencies --}}
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
                qr-snake-grow   2.4s ease-in-out infinite,
                qr-snake-travel 2.4s linear infinite;
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

        /* ── Helpers ──────────────────────────────────────────────────────── */
        const $ = id => document.getElementById(id);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

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

        /* ── QR Modal ─────────────────────────────────────────────────────── */
        function setQrState(state) {
            // state: 'loading' | 'ready' | 'error'
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

        async function openQrModal(accountId, qrUrl) {
            openModal('payment-qr-modal');
            setQrState('loading');
            stopSnake();

            try {
                const res  = await fetch(qrUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                const d    = json.data;

                if (!d || !d.payload) throw new Error('no_payload');

                // Render QR onto canvas
                const canvas = $('qr-canvas');
                const size   = 220;
                canvas.width  = size;
                canvas.height = size;

                await QRCode.toCanvas(canvas, d.payload, {
                    width:            size,
                    margin:           1,
                    errorCorrectionLevel: 'M',
                    color: { dark: '#000000', light: '#ffffff' },
                });

                // Populate info fields
                $('qr-bank-badge').textContent    = d.bank_name || d.bank_code;
                $('qr-acc-name').textContent      = d.account_name || '';
                $('qr-acc-number').textContent    = d.account_number || '';

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

            } catch (err) {
                console.error('QR fetch error:', err);
                setQrState('error');
            }
        }

        /* ── Bind QR buttons (event delegation) ──────────────────────────── */
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-qr-btn]');
            if (btn) {
                openQrModal(btn.dataset.accountId, btn.dataset.qrUrl);
            }
        });

        /* Close QR modal */
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-close-qr]') || (e.target.id === 'payment-qr-modal')) {
                closeModal('payment-qr-modal');
                stopSnake();
            }
        });

        /* ── Delete modal ─────────────────────────────────────────────────── */
        let pendingDeleteId = null;

        window.deleteAccount = function (id) {
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
            closeModal('payment-delete-modal');

            try {
                const res = await fetch(
                    window.__adminRoomBase + '/payment-accounts/' + pendingDeleteId,
                    { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const json = await res.json();
                if (json.data?.disabled) {
                    showNotice('{{ __('admin.account_deleted_ok') }}');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showNotice('{{ __('admin.error_generic') }}', 'error');
                }
            } catch {
                showNotice('{{ __('admin.error_generic') }}', 'error');
            }
            pendingDeleteId = null;
        });

        /* ── Add / Edit form ──────────────────────────────────────────────── */
        window.editPaymentAccount = function (id, bankCode, bankName, accountNumber, accountName, isDefault) {
            $('payment-account-id').value = id;
            $('acc-bank-code').value      = bankCode;
            $('acc-number').value         = accountNumber;
            $('acc-name').value           = accountName;
            $('acc-default').checked      = isDefault;
            $('payment-form-title').textContent = '{{ __('admin.edit_account') }}';
            $('payment-cancel-edit').classList.remove('hidden');
            $('acc-bank-code').scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        window.resetForm = function () {
            $('add-payment-form').reset();
            $('payment-account-id').value = '';
            $('payment-form-title').textContent = '{{ __('admin.add_new_account') }}';
            $('payment-cancel-edit').classList.add('hidden');
        };

        /* Auto-fill bank name from select */
        $('acc-bank-code')?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            // bank name will be sent separately if field still exists — but we removed bank_name input,
            // so we pass it via data attribute to the JS payload
            this.dataset.bankName = opt.dataset.bankName ?? '';
        });

        /* Form submit */
        $('add-payment-form')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const accountId = $('payment-account-id').value;
            const bankCode  = $('acc-bank-code').value;
            const bankName  = $('acc-bank-code').options[$('acc-bank-code').selectedIndex]?.dataset.bankName ?? bankCode;
            const body = JSON.stringify({
                bank_code:      bankCode,
                bank_name:      bankName,
                account_number: $('acc-number').value.trim(),
                account_name:   $('acc-name').value.trim().toUpperCase(),
                is_default:     $('acc-default').checked,
            });

            const url    = window.__adminRoomBase + '/payment-accounts' + (accountId ? '/' + accountId : '');
            const method = accountId ? 'PATCH' : 'POST';

            try {
                const res  = await fetch(url, {
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
                showNotice(accountId ? '{{ __('admin.account_updated_ok') }}' : '{{ __('admin.account_created_ok') }}');
                resetForm();
                setTimeout(() => location.reload(), 800);
            } catch {
                showNotice('{{ __('admin.error_generic') }}', 'error');
            }
        });

        /* Expose room base URL for AJAX calls */
        window.__adminRoomBase = '{{ rtrim(url()->current(), '/') }}'.replace(/\/payment-accounts\/settings$/, '');

    }());
    </script>
    @endpush

</x-admin.layout>