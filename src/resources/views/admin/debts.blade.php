<x-admin.layout :title="__('admin.debts_management')" active="debts" :room="$room">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-outline mb-1">
                <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-primary transition-colors">Admin</a>
                <span>/</span>
                <span>Rooms</span>
                <span>/</span>
                <span class="text-on-surface font-semibold">{{ $room->name }}</span>
                <span>/</span>
                <span class="text-primary font-bold">{{ __('admin.debts') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.debts_management') }}</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="exportDebtCSV()" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant text-on-surface rounded text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors">
                <span class="material-symbols-outlined text-[16px] text-primary">download</span>
                <span>{{ __('admin.export_csv_btn') }}</span>
            </button>
            <button type="button" onclick="triggerBotReminder()" class="px-3.5 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">notifications_active</span>
                <span>{{ __('admin.bot_reminder_btn') }}</span>
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
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <x-admin.search-input id="debt-search" placeholder="{{ __('admin.search_debts_placeholder') }}" containerClass="relative w-full max-w-md" />
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button" data-status="all" class="debt-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-primary text-on-primary transition-colors">{{ __('admin.filter_all') }}</button>
            <button type="button" data-status="unpaid" class="debt-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_debt_unpaid') }}</button>
            <button type="button" data-status="partial" class="debt-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_debt_partial') }}</button>
            <button type="button" data-status="paid" class="debt-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_debt_paid') }}</button>
        </div>
    </div>

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
                            $member = $debt->roomUser?->globalUser?->name ?? $debt->roomUser?->display_name ?? 'Member #' . $debt->room_user_id;
                            $stClass = match($debt->status) {
                                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'partial' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'unpaid' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                                default => 'bg-surface-container text-secondary border-outline-variant'
                            };
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors" data-debt-row data-status="{{ $debt->status }}" data-search="{{ strtolower($member . ' ' . $debt->id . ' ' . ($debt->campaign?->title ?? '')) }}">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface text-sm flex items-center gap-1.5">
                                    <span>{{ $member }}</span>
                                    <span class="text-[11px] font-mono text-outline font-normal">#DB-{{ $debt->id }}</span>
                                </div>
                                <div class="text-secondary text-[11px] mt-0.5">{{ $debt->roomUser?->globalUser?->email ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-on-surface">{{ $debt->campaign?->title ?? 'N/A' }}</div>
                                <div class="text-[11px] text-outline">{{ $debt->created_at ? $debt->created_at->format('H:i d/m/Y') : '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $stClass }}">
                                    {{ ucfirst($debt->status) }}
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
                                    @if($debt->remaining_amount > 0)
                                        <button type="button" onclick="openRecordPaymentModal({{ $debt->id }}, {{ $debt->remaining_amount }}, '{{ addslashes($member) }}')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded text-[11px] font-semibold flex items-center gap-1 shadow-2xs transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">payments</span>
                                            <span>{{ __('admin.record_payment_btn') }}</span>
                                        </button>
                                        <button type="button" onclick="openAdjustDebtModal({{ $debt->id }}, {{ $debt->remaining_amount }}, '{{ addslashes($member) }}')" class="p-1 text-secondary hover:text-primary rounded hover:bg-surface-container transition-colors" title="{{ __('admin.adjust_debt_btn') }}">
                                            <span class="material-symbols-outlined text-[16px]">tune</span>
                                        </button>
                                    @else
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

    <!-- Generic Debt Action Modal -->
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
</x-admin.layout>
