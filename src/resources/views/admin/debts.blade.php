<x-admin.layout :title="__('admin.debts_management')" active="debts" :room="$room">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.debts_management') }}</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="exportDebtCSV()" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant text-on-surface rounded text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors">
                <span class="material-symbols-outlined text-[16px] text-primary">download</span>
                <span>{{ __('admin.export_csv_btn') }}</span>
            </button>
        </div>
    </div>

    <!-- 4 Financial KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.total_store_spending') }}</span>
                <span class="material-symbols-outlined text-[20px] text-secondary">receipt_long</span>
            </div>
            <div class="text-2xl font-bold font-mono text-on-surface mt-2">{{ number_format($totalSpent ?? 0) }} ₫</div>
            <div class="text-[11px] text-outline mt-1">{{ __('admin.kpi_total_spent_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.total_member_collected') }}</span>
                <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
            </div>
            <div class="text-2xl font-bold font-mono text-emerald-600 mt-2">{{ number_format($totalCollected ?? 0) }} ₫</div>
            <div class="text-[11px] text-emerald-700 mt-1">{{ __('admin.kpi_collected_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.store_debt_pending') }}</span>
                <span class="material-symbols-outlined text-[20px] text-blue-600">storefront</span>
            </div>
            <div class="text-2xl font-bold font-mono text-blue-600 mt-2">{{ number_format($storeDebtPending ?? 0) }} ₫</div>
            <div class="text-[11px] text-outline mt-1">{{ __('admin.kpi_store_debt_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.member_debt_remaining') }}</span>
                <span class="material-symbols-outlined text-[20px] text-amber-600">pending_actions</span>
            </div>
            <div class="text-2xl font-bold font-mono text-amber-600 mt-2">{{ number_format($memberDebtRemaining ?? 0) }} ₫</div>
            <div class="text-[11px] text-amber-700 mt-1">{{ __('admin.kpi_member_debt_desc') }}</div>
        </div>
    </div>

    <!-- Filter and Search Toolbar -->
    <form id="debts-filter-form" method="GET" action="{{ route('admin.debts.page', $room) }}" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <x-admin.search-input id="debt-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('admin.search_debts_placeholder') }}" containerClass="relative w-full max-w-md" />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <select id="debt-status-filter" name="status" class="h-9 px-3 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('admin.filter_all') }}</option>
                @foreach($statusFilters as $statusFilter)
                    <option value="{{ $statusFilter['value'] }}" {{ ($filters['status'] ?? '') === $statusFilter['value'] ? 'selected' : '' }}>{{ $statusFilter['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-9 inline-flex items-center gap-1.5 px-3 rounded-lg bg-primary text-on-primary text-xs font-semibold hover:bg-primary-container transition-colors">
                <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                {{ __('admin.filter_apply') }}
            </button>
            @if(trim((string) ($filters['search'] ?? '')) !== '' || ($filters['status'] ?? 'all') !== 'all')
                <a id="debt-clear-filters" href="{{ route('admin.debts.page', $room) }}" class="h-9 inline-flex items-center gap-1.5 px-3 rounded-lg border border-outline-variant bg-surface text-on-surface text-xs font-semibold hover:bg-surface-container transition-colors no-underline">
                    <span class="material-symbols-outlined text-[16px]">filter_alt_off</span>
                    {{ __('admin.filter_clear') }}
                </a>
            @endif
        </div>
    </form>

    <!-- Debts Table Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-outline font-mono uppercase text-[11px] border-b border-outline-variant">
                        <th class="py-3 px-4">{{ __('admin.debt_member') }}</th>
                        <th class="py-3 px-4">{{ __('admin.origin_campaign') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_status') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('admin.th_original_debt') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('admin.remaining_debt') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="debts-tbody" class="divide-y divide-outline-variant/50">
                    @forelse($debts as $debt)
                        @php
                            $member        = $debt->roomUser?->globalUser?->name ?? $debt->roomUser?->display_name ?? 'Member #' . $debt->room_user_id;
                            $memberEmail   = $debt->roomUser?->globalUser?->email ?? '';
                            $memberCode    = $debt->roomUser?->user_code ?? '';
                            $campaignName  = $debt->campaign?->name ?? 'N/A';
                            $updatedAt     = $debt->payment_requested_at ? $debt->payment_requested_at->format('H:i d/m/Y') : ($debt->updated_at ? $debt->updated_at->format('H:i d/m/Y') : '');
                            $createdAt     = $debt->created_at ? $debt->created_at->format('H:i d/m/Y') : '';
                            $transferContent = $debt->payment_content ?: ($debt->roomUser?->user_code ?: ('DRINKFLOW-DEBT-' . ($debt->roomUser?->id ?? $debt->room_user_id)));
                            $debtStatusValue = $debt->status instanceof \BackedEnum ? $debt->status->value : (string) $debt->status;
                            $isPayAll      = !empty($debt->payment_content) && $debt->roomUser && $debt->payment_content === $debt->roomUser->user_code;
                            $userPendingDebts = ($isPayAll && $debt->roomUser) ? $debt->roomUser->debts->map(fn($d) => [
                                'id' => $d->id,
                                'code' => $d->code ?? 'N/A',
                                'campaign' => $d->campaign?->name ?? 'N/A',
                                'amount' => (int) ($d->remaining_amount > 0 ? $d->remaining_amount : $d->original_amount),
                                'note' => $d->note,
                            ])->values() : collect();
                            $totalPendingAmount = $isPayAll ? (int) $userPendingDebts->sum('amount') : (int) $debt->remaining_amount;
                            $stClass = match($debtStatusValue) {
                                'paid'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'pending' => 'bg-amber-100 text-amber-900 border-amber-300 font-bold',
                                'partial' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'unpaid'  => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                                default   => 'bg-surface-container text-secondary border-outline-variant'
                            };
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors"
                            data-debt-row
                            data-debt-id="{{ $debt->id }}"
                            data-status="{{ $debtStatusValue }}"
                            data-search="{{ strtolower($member . ' ' . $debt->id . ' ' . $campaignName) }}">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface text-sm flex items-center gap-1.5">
                                    <span>{{ $member }}</span>
                                    <span class="text-[11px] font-mono text-outline font-normal">{{ $debt->code ?? 'N/A' }}</span>
                                </div>
                                <div class="text-secondary text-[11px] mt-0.5">{{ $debt->roomUser?->globalUser?->email ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-on-surface">{{ $debt->campaign?->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-outline">{{ $debt->created_at ? $debt->created_at->format('H:i d/m/Y') : '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $stClass }}">
                                    {{ __('admin.status_' . $debtStatusValue) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-secondary">
                                {{ number_format($debt->original_amount ?? 0) }} ₫
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-sm {{ $debt->remaining_amount > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                {{ number_format($debt->remaining_amount ?? 0) }} ₫
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($debtStatusValue === 'pending')
                                        <button
                                            type="button"
                                            class="px-2.5 py-1 bg-amber-500 text-white hover:bg-amber-600 rounded text-[11px] font-bold flex items-center gap-1 shadow-2xs transition-colors cursor-pointer"
                                            onclick="openApproveDebtModal({
                                                id: {{ $debt->id }},
                                                member: '{{ addslashes($member) }}',
                                                memberEmail: '{{ addslashes($memberEmail) }}',
                                                memberCode: '{{ addslashes($memberCode) }}',
                                                campaign: '{{ addslashes($campaignName) }}',
                                                amount: {{ (int) ($isPayAll ? $totalPendingAmount : $debt->remaining_amount) }},
                                                updatedAt: '{{ $updatedAt }}',
                                                createdAt: '{{ $createdAt }}',
                                                transferContent: '{{ addslashes($transferContent) }}',
                                                isPayAll: {{ $isPayAll ? 'true' : 'false' }},
                                                pendingDebts: {{ Js::from($userPendingDebts) }}
                                            })"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">pending_actions</span>
                                            <span>{{ __('admin.approve_payment_btn') }}</span>
                                        </button>
                                    @endif
                                    @if($debt->remaining_amount > 0 && $debtStatusValue !== 'pending')
                                        <button type="button" onclick="openRecordPaymentModal({{ $debt->id }}, {{ $debt->remaining_amount }}, '{{ addslashes($member) }}')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded text-[11px] font-semibold flex items-center gap-1 shadow-2xs transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">payments</span>
                                            <span>{{ __('admin.record_payment_btn') }}</span>
                                        </button>
                                        <button type="button" onclick="openAdjustDebtModal({{ $debt->id }}, {{ $debt->remaining_amount }}, '{{ addslashes($member) }}')" class="p-1 text-secondary hover:text-primary rounded hover:bg-surface-container transition-colors" title="{{ __('admin.adjust_debt_btn') }}">
                                            <span class="material-symbols-outlined text-[16px]">tune</span>
                                        </button>
                                    @elseif($debt->remaining_amount <= 0)
                                        <span class="text-[11px] text-emerald-700 font-semibold flex items-center gap-0.5">
                                            <span class="material-symbols-outlined text-[14px]">verified</span>
                                            <span>{{ __('admin.settled_badge') }}</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-outline">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">payments</span>
                                    <p class="font-medium text-sm">{{ __('admin.no_debts_found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($debts->hasPages())
            <div class="p-4 border-t border-outline-variant">
                {{ $debts->links() }}
            </div>
        @endif
    </div>

    <!-- Generic Debt Action Modal (Record Payment / Adjust) -->
    <div id="debt-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div id="debt-backdrop" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-md bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant mb-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary" id="debt-modal-icon">payments</span>
                    <h3 class="font-bold text-base text-on-surface" id="debt-modal-title">{{ __('admin.record_payment') }}</h3>
                </div>
                <button type="button" onclick="closeDebtModal()" class="text-outline hover:text-on-surface">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <div id="debt-modal-body"></div>
        </div>
    </div>

    <!-- Approve Payment Detail Modal -->
    <div id="approve-debt-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/65 p-4 backdrop-blur-sm">
        <div id="approve-debt-backdrop" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-lg bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant bg-amber-50/60 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]" id="approve-modal-icon">pending_actions</span>
                    </span>
                    <div>
                        <h3 class="font-bold text-base text-on-surface" id="approve-modal-title">{{ __('admin.payment_approval_modal_title') }}</h3>
                        <p class="text-[11px] text-outline mt-0.5" id="approve-modal-subtitle">{{ __('admin.payment_approval_modal_subtitle') }}</p>
                    </div>
                </div>
                <button type="button" id="approve-modal-close" class="w-8 h-8 flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container rounded-lg transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1 text-xs">
                <!-- User Requester -->
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[18px] text-outline mt-0.5 shrink-0">account_circle</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-mono uppercase text-outline tracking-wider mb-0.5">{{ __('admin.requester_user') }}</div>
                        <div class="font-bold text-on-surface text-sm" id="approve-modal-member">—</div>
                        <div class="text-xs text-outline mt-0.5" id="approve-modal-member-meta">—</div>
                    </div>
                    <span id="approve-modal-status-badge"
                          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-amber-100 text-amber-900 border-amber-300 shrink-0">
                        <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
                        <span id="approve-modal-badge-text">{{ __('admin.status_pending') }}</span>
                    </span>
                </div>

                <!-- Divider -->
                <div class="border-t border-outline-variant/50"></div>

                <!-- Grid Info (Single debt view) -->
                <div class="grid grid-cols-2 gap-3" id="approve-modal-single-info">
                    <!-- Request time -->
                    <div class="bg-surface-container rounded-lg p-3">
                        <div class="text-[11px] font-mono uppercase text-outline tracking-wider mb-1">{{ __('admin.request_time') }}</div>
                        <div class="text-xs font-semibold text-on-surface" id="approve-modal-time">—</div>
                    </div>
                    <!-- Campaign -->
                    <div class="bg-surface-container rounded-lg p-3">
                        <div class="text-[11px] font-mono uppercase text-outline tracking-wider mb-1">{{ __('admin.origin_campaign') }}</div>
                        <div class="text-xs font-semibold text-on-surface truncate" id="approve-modal-campaign">—</div>
                    </div>
                </div>

                <!-- Transfer content / Note -->
                <div class="bg-surface-container rounded-lg p-3">
                    <div class="text-[11px] font-mono uppercase text-outline tracking-wider mb-1">{{ __('admin.request_content') }}</div>
                    <div class="text-xs font-mono text-primary font-bold tracking-wide" id="approve-modal-content">—</div>
                </div>

                <!-- Debts Breakdown Container (Shown when isPayAll) -->
                <div id="approve-modal-breakdown-container" class="hidden space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-mono uppercase text-outline tracking-wider">
                        <span>{{ __('admin.debts_breakdown_title') }}</span>
                        <span id="approve-modal-breakdown-count" class="font-bold text-primary"></span>
                    </div>
                    <div class="border border-outline-variant/60 rounded-xl overflow-hidden divide-y divide-outline-variant/40 max-h-48 overflow-y-auto bg-surface-container-low" id="approve-modal-breakdown-list">
                    </div>
                </div>

                <!-- Amount to clear -->
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-mono uppercase text-emerald-700 tracking-wider mb-0.5" id="approve-modal-amount-label">{{ __('admin.approval_amount_to_clear') }}</div>
                        <div class="text-2xl font-bold font-mono text-emerald-700" id="approve-modal-amount">—</div>
                    </div>
                    <span class="material-symbols-outlined text-[32px] text-emerald-400">payments</span>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-outline-variant flex items-center justify-end gap-3 bg-surface-container-low/40 shrink-0">
                <button type="button" id="approve-modal-cancel"
                        class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                    {{ __('admin.cancel_btn') }}
                </button>
                <button type="button" id="approve-modal-confirm"
                        data-debt-id=""
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-2 shadow-xs transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[15px]">check_circle</span>
                    <span id="approve-modal-confirm-text">{{ __('admin.confirm_approve_btn') }}</span>
                </button>
            </div>
        </div>
    </div>
</x-admin.layout>
