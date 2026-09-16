<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'debts'" :title="__('room.debts.page_title')">
    <div class="flex flex-col w-full gap-space-lg" x-data="{
        qrModalOpen: false,
        qrData: {
            bankName: '{{ $vietqrData['bank_name'] ?? 'MB Bank' }}',
            accountNumber: '{{ $vietqrData['account_number'] ?? '0388999888' }}',
            accountName: '{{ $vietqrData['account_name'] ?? 'DRINKFLOW ADMIN' }}',
            amount: {{ $totalUnpaidAmount }},
            formattedAmount: '{{ number_format($totalUnpaidAmount, 0, ',', '.') }}đ',
            transferContent: '{{ $vietqrData['transfer_content'] ?? ('DRINKFLOW-DEBT-' . $roomUser->id) }}',
            qrUrl: '{{ $vietqrData['qr_url'] ?? ('https://img.vietqr.io/image/MB-0388999888-compact2.png?amount=' . $totalUnpaidAmount . '&addInfo=DRINKFLOW-DEBT-' . $roomUser->id) }}'
        },
        openQr(amount, formattedAmount, content, customQrUrl) {
            this.qrData.amount = amount;
            this.qrData.formattedAmount = formattedAmount;
            this.qrData.transferContent = content;
            this.qrData.qrUrl = customQrUrl || ('https://img.vietqr.io/image/MB-0388999888-compact2.png?amount=' + amount + '&addInfo=' + encodeURIComponent(content));
            this.qrModalOpen = true;
        },
        copyText(text) {
            navigator.clipboard?.writeText(text);
            alert('{{ __('room.debts.copied_alert', ['text' => '']) }}' + text);
        }
    }">
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-surface-container-lowest p-3 sm:p-3.5 rounded-xl shadow-2xs border border-outline-variant/30">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-primary shrink-0">
                    <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm sm:text-base text-on-surface font-bold">{{ __('room.debts.page_title') }}</span>
                        @if($totalUnpaidAmount > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container text-[11px] font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-error animate-ping"></span>
                                {{ __('room.debts.unpaid_count', ['count' => $unpaidDebts->count()]) }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed text-[11px] font-semibold">
                                <span class="material-symbols-outlined text-[13px]">check</span>
                                {{ __('room.debts.all_paid') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">{{ __('room.debts.subtitle') }}</p>
                </div>
            </div>
            @if($totalUnpaidAmount > 0)
                <button class="px-3.5 py-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold shadow-2xs transition-all inline-flex items-center gap-1.5 self-start md:self-auto cursor-pointer"
                        @click="openQr({{ $totalUnpaidAmount }}, '{{ number_format($totalUnpaidAmount, 0, ',', '.') }}đ', '{{ $vietqrData['transfer_content'] ?? ('DRINKFLOW-DEBT-' . $roomUser->id) }}', '{{ $vietqrData['qr_url'] ?? '' }}')">
                    <span class="material-symbols-outlined text-[17px]">qr_code_2</span>
                    <span>{{ __('room.debts.pay_all', ['amount' => number_format($totalUnpaidAmount, 0, ',', '.') . 'đ']) }}</span>
                </button>
            @endif
        </div>

        <!-- 3 Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <!-- Card 1: Unpaid Debt -->
            <div class="bg-surface-container-lowest rounded-xl p-3.5 sm:p-4 shadow-2xs border border-outline-variant/30 relative overflow-hidden flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-xs text-on-surface-variant font-semibold">{{ __('room.debts.unpaid_title') }}</span>
                    <span class="w-7 h-7 rounded-lg bg-error-container text-on-error-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-[17px]">pending_actions</span>
                    </span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-lg sm:text-xl text-error tracking-tight font-bold font-tabular-nums">
                            {{ number_format($totalUnpaidAmount, 0, ',', '.') }}đ
                        </span>
                        <span class="text-[11px] text-error bg-error-container px-1.5 py-0.5 rounded font-medium">
                            {{ __('room.debts.unpaid_count', ['count' => $unpaidDebts->count()]) }}
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-on-surface-variant leading-snug">{{ __('room.debts.unpaid_hint') }}</p>
                </div>
                <div class="mt-3 pt-2.5 flex items-center justify-between text-on-surface-variant text-[11px] border-t border-outline-variant/20">
                    <span class="flex items-center gap-1 {{ $totalUnpaidAmount > 0 ? 'text-error' : 'text-primary' }}">
                        <span class="material-symbols-outlined text-[14px]">{{ $totalUnpaidAmount > 0 ? 'error' : 'check_circle' }}</span>
                        {{ $totalUnpaidAmount > 0 ? __('room.debts.transfer_needed') : __('room.debts.no_debt') }}
                    </span>
                    <a class="text-primary hover:underline font-medium" href="#unpaid-bills">{{ __('room.debts.debt_details') }}</a>
                </div>
            </div>

            <!-- Card 2: Paid this month -->
            <div class="bg-surface-container-lowest rounded-xl p-3.5 sm:p-4 shadow-2xs border border-outline-variant/30 relative overflow-hidden flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-xs text-on-surface-variant font-semibold">{{ __('room.debts.paid_this_month') }}</span>
                    <span class="w-7 h-7 rounded-lg bg-primary-fixed text-on-primary-fixed-variant flex items-center justify-center">
                        <span class="material-symbols-outlined text-[17px]">check_circle</span>
                    </span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-lg sm:text-xl text-primary tracking-tight font-bold font-tabular-nums">
                            {{ number_format($totalPaidMonthAmount, 0, ',', '.') }}đ
                        </span>
                        <span class="text-[11px] text-primary bg-primary-fixed px-1.5 py-0.5 rounded font-medium">
                            {{ __('room.debts.paid_count', ['count' => $totalPaidMonthCount]) }}
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-on-surface-variant leading-snug">{{ __('room.debts.paid_reconciliation_hint') }}</p>
                </div>
                <div class="mt-3 pt-2.5 flex items-center justify-between text-[11px] text-on-surface-variant border-t border-outline-variant/20">
                    <span class="flex items-center gap-1 text-primary">
                        <span class="material-symbols-outlined text-[14px]">verified</span>
                        {{ __('room.debts.matched_percent') }}
                    </span>
                    <span class="text-on-surface-variant font-tabular-nums">{{ now()->format('m/Y') }}</span>
                </div>
            </div>

            <!-- Card 3: Sponsor Saved -->
            <div class="bg-surface-container-lowest rounded-xl p-3.5 sm:p-4 shadow-2xs border border-outline-variant/30 relative overflow-hidden flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-xs text-on-surface-variant font-semibold">{{ __('room.debts.sponsor_saved_title') }}</span>
                    <span class="w-7 h-7 rounded-lg bg-secondary-container text-on-secondary-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-[17px]">redeem</span>
                    </span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-lg sm:text-xl text-secondary tracking-tight font-bold font-tabular-nums">
                            {{ number_format($totalSponsorAmount ?? 0, 0, ',', '.') }}đ
                        </span>
                        <span class="text-[11px] text-secondary bg-secondary-container px-1.5 py-0.5 rounded font-medium">{{ __('room.debts.sponsored_label') }}</span>
                    </div>
                    <p class="mt-1 text-[11px] text-on-surface-variant leading-snug">{{ __('room.debts.sponsor_saved_desc') }}</p>
                </div>
                <div class="mt-3 pt-2.5 flex items-center justify-between text-[11px] text-on-surface-variant border-t border-outline-variant/20">
                    <span class="flex items-center gap-1 text-on-surface-variant">
                        <span class="material-symbols-outlined text-[14px]">savings</span>
                        {{ __('room.debts.welfare_label') }}
                    </span>
                    <span class="text-secondary font-medium">{{ __('room.debts.saved_percent') }}</span>
                </div>
            </div>
        </div>

        <!-- Debt Ledger Table -->
        <div class="w-full flex flex-col gap-3" id="unpaid-bills">
            <div class="bg-surface-container-lowest rounded-xl shadow-2xs overflow-hidden border border-outline-variant/30">
                <div class="p-3 sm:p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-surface-container-lowest border-b border-outline-variant/30">
                    <div>
                        <h3 class="text-xs sm:text-sm text-on-surface font-bold">{{ __('room.debts.history_title') }}</h3>
                        <p class="text-[11px] text-on-surface-variant mt-0.5">{{ __('room.debts.subtitle') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] px-2 py-0.5 rounded bg-surface-container-high text-on-surface font-medium">{{ __('room.debts.all_debts_badge') }}</span>
                    </div>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface-variant text-[10px] sm:text-[11px] font-bold uppercase tracking-wider border-b border-outline-variant/20">
                                <th class="py-2.5 px-3">{{ __('room.debts.table_tx_id') }}</th>
                                <th class="py-2.5 px-2.5">{{ __('room.debts.table_time') }}</th>
                                <th class="py-2.5 px-2.5">{{ __('room.debts.table_campaign') }}</th>
                                <th class="py-2.5 px-2.5 text-right">{{ __('room.debts.table_total') }}</th>
                                <th class="py-2.5 px-2.5 text-right">{{ __('room.debts.table_paid') }}</th>
                                <th class="py-2.5 px-2.5 text-right">{{ __('room.debts.table_remaining') }}</th>
                                <th class="py-2.5 px-2.5 text-center">{{ __('room.debts.table_status') }}</th>
                                <th class="py-2.5 px-3 text-center">{{ __('room.debts.table_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-on-surface divide-y divide-outline-variant/20">
                            @forelse($debts as $debt)
                                @php
                                    $debtStatus = $debt->status instanceof \BackedEnum ? $debt->status->value : (string) $debt->status;
                                    $isPaid = $debtStatus === \App\Enums\DebtStatus::Paid->value;
                                @endphp
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-2.5 px-3 font-tabular-nums font-bold text-primary font-mono text-xs">{{ $debt->code ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-2.5 text-on-surface-variant whitespace-nowrap text-[11px]">{{ $debt->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 px-2.5 font-medium">
                                        <div class="flex flex-col">
                                            <span class="text-on-surface font-semibold text-xs">{{ $debt->campaign?->name ?? __('global.common.campaign') }}</span>
                                            <span class="text-[10px] text-on-surface-variant">{{ $debt->sponsor_description ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-tabular-nums text-xs">{{ number_format($debt->original_amount, 0, ',', '.') }}đ</td>
                                    <td class="py-2.5 px-2.5 text-right font-tabular-nums text-primary text-xs">{{ number_format($debt->paid_amount, 0, ',', '.') }}đ</td>
                                    <td class="py-2.5 px-2.5 text-right font-tabular-nums font-bold text-xs {{ $debt->remaining_amount > 0 ? 'text-error' : 'text-on-surface' }}">
                                        {{ number_format($debt->remaining_amount, 0, ',', '.') }}đ
                                    </td>
                                    <td class="py-2.5 px-2.5 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] {{ $isPaid ? 'bg-primary-fixed text-on-primary-fixed-variant' : 'bg-error-container text-on-error-container' }} font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isPaid ? 'bg-primary' : 'bg-error' }}"></span>
                                            {{ $isPaid ? __('room.debts.status_paid') : __('room.debts.status_unpaid') }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if(!$isPaid && $debt->remaining_amount > 0)
                                            <button class="px-2.5 py-1 rounded bg-primary text-on-primary text-xs hover:bg-primary-container transition-all inline-flex items-center gap-1 shadow-2xs cursor-pointer font-medium"
                                                    @click="openQr({{ (int)$debt->remaining_amount }}, '{{ number_format($debt->remaining_amount, 0, ',', '.') }}đ', 'DFDB{{ $debt->id }} {{ $roomUser->user_code }}')">
                                                <span class="material-symbols-outlined text-[14px]">qr_code</span>
                                                <span>{{ __('room.debts.btn_view_qr') }}</span>
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] text-primary font-medium">
                                                <span class="material-symbols-outlined text-[13px]">verified</span>
                                                {{ __('room.debts.status_done') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-on-surface-variant text-xs">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <span class="material-symbols-outlined text-[28px] text-outline-variant" aria-hidden="true">receipt_long</span>
                                            <span>{{ __('room.debts.all_settled') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($debts->hasPages())
                    <div class="p-3 border-t border-outline-variant/30 text-xs">
                        {{ $debts->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- VietQR Modal -->
        <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/50 backdrop-blur-xs transition-opacity duration-200" role="dialog">
            <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl shadow-2xl border border-outline-variant overflow-hidden flex flex-col" @click.outside="qrModalOpen = false">
                <div class="p-3 sm:p-3.5 border-b border-outline-variant/30 flex items-center justify-between bg-surface-container-low">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-primary-fixed flex items-center justify-center text-on-primary-fixed-variant">
                            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm text-on-surface font-bold">{{ __('room.debts.vietqr_modal_title') }}</h3>
                            <p class="text-[11px] text-on-surface-variant">{{ $room->name }}</p>
                        </div>
                    </div>
                    <button class="w-7 h-7 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer" @click="qrModalOpen = false">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                <div class="p-3.5 sm:p-4 flex flex-col gap-3">
                    <div class="flex flex-col items-center justify-center p-3 bg-surface-container-low rounded-xl border border-outline-variant/40">
                        <div class="bg-surface-container-lowest p-2.5 rounded-xl shadow-2xs border border-outline-variant flex flex-col items-center">
                            <img :src="qrData.qrUrl" alt="VietQR" class="w-44 h-44 object-contain rounded" loading="lazy"/>
                            <div class="mt-1.5 flex items-center gap-1 text-[10px] text-secondary font-medium">
                                <span class="material-symbols-outlined text-[13px]">bolt</span>
                                <span>{{ __('room.debts.vietqr_scan_hint') }}</span>
                            </div>
                        </div>
                        <div class="mt-2.5 text-center">
                            <span class="text-[11px] text-on-surface-variant">{{ __('room.debts.vietqr_amount_label') }}</span>
                            <div class="text-lg sm:text-xl font-bold text-error tracking-tight font-tabular-nums" x-text="qrData.formattedAmount"></div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-2.5 text-xs">
                        <div class="flex items-center justify-between py-1 border-b border-surface-container-high text-xs">
                            <span class="text-on-surface-variant">{{ __('room.debts.vietqr_bank_label') }}</span>
                            <span class="font-semibold text-on-surface flex items-center gap-1">
                                <span class="px-1.5 py-0.5 rounded bg-surface-container-high text-[10px] font-bold text-primary" x-text="qrData.bankName"></span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-surface-container-high text-xs">
                            <span class="text-on-surface-variant">{{ __('room.debts.vietqr_account_number') }}</span>
                            <div class="flex items-center gap-1">
                                <span class="font-tabular-nums font-bold text-on-surface text-xs" x-text="qrData.accountNumber"></span>
                                <button class="p-0.5 rounded hover:bg-surface-container-high text-primary transition-colors flex items-center cursor-pointer" @click="copyText(qrData.accountNumber)">
                                    <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-surface-container-high text-xs">
                            <span class="text-on-surface-variant">{{ __('room.debts.vietqr_account_name') }}</span>
                            <span class="font-semibold text-on-surface uppercase text-xs" x-text="qrData.accountName"></span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 bg-primary-fixed/20 px-2 rounded-lg mt-0.5 text-xs">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-on-primary-fixed-variant font-medium">{{ __('room.debts.vietqr_transfer_content') }}</span>
                                <span class="font-tabular-nums font-bold text-primary tracking-wide text-xs" x-text="qrData.transferContent"></span>
                            </div>
                            <button class="p-0.5 rounded hover:bg-primary-fixed text-primary transition-colors flex items-center cursor-pointer" @click="copyText(qrData.transferContent)">
                                <span class="material-symbols-outlined text-[14px]">content_copy</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-room.layout>
