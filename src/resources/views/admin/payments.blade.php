<x-admin.layout :title="__('admin.vietqr_accounts_title')" active="payment_accounts" :room="$room">
    <!-- Header & Action Ribbon -->
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

    <!-- Notice Notification Banner -->
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Payment Accounts List -->
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
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-on-surface font-mono">{{ $acc->account_number }}</span>
                                        @if($acc->is_default)
                                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">{{ __('admin.default_badge') }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $accStatusVal === 'active' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500' }}">
                                            {{ __('admin.status_' . $accStatusVal) }}
                                        </span>
                                    </div>
                                    <div class="text-xs font-semibold text-secondary uppercase mt-0.5">{{ $acc->account_name }}</div>
                                    <div class="text-[11px] text-outline mt-0.5">{{ __('admin.bank_label') }} {{ $acc->bank_name ?: $acc->bank_code }} {{ $acc->branch ? '• ' . $acc->branch : '' }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" onclick="previewQR('{{ $acc->bank_code }}', '{{ $acc->account_number }}', '{{ addslashes($acc->account_name) }}')" class="px-2.5 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded text-xs font-semibold flex items-center gap-1 border border-outline-variant transition-colors">
                                    <span class="material-symbols-outlined text-[14px] text-primary">qr_code_2</span>
                                    <span>{{ __('admin.view_qr') }}</span>
                                </button>
                                <button type="button" onclick="deleteAccount({{ $acc->id }})" class="p-1.5 text-secondary hover:text-rose-600 rounded hover:bg-surface-container transition-colors" title="Delete account">
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

        <!-- Right: Add / Update Account Form & QR Preview -->
        <div class="space-y-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-outline-variant mb-4">
                    <span class="material-symbols-outlined text-[20px] text-primary">add_card</span>
                    <h2 class="font-bold text-sm text-on-surface">{{ __('admin.add_new_account') }}</h2>
                </div>

                <form id="add-payment-form" data-loading-form="true" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.bank_code') }}:</label>
                        <select id="acc-bank-code" data-searchable="true" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface font-semibold" required>
                            @foreach($banks ?? app(\App\Services\Common\BankService::class)->getAllBanks() as $bank)
                                <option value="{{ $bank['code'] }}" data-bank-name="{{ $bank['name'] }}">{{ $bank['code'] }} - {{ $bank['short_name'] }} ({{ $bank['name'] }})</option>
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

                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.branch_optional_label') }}</label>
                        <input type="text" id="acc-branch" placeholder="Hà Nội" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                    </div>

                    <div class="pt-1 flex items-center gap-2">
                        <input type="checkbox" id="acc-default" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                        <label for="acc-default" class="text-on-surface font-semibold cursor-pointer">{{ __('admin.set_as_default') }}</label>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full h-9 bg-primary hover:bg-primary/90 text-on-primary rounded font-bold transition-colors">
                            {{ __('admin.save_vietqr_account') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Dynamic VietQR Card Preview -->
            <div id="qr-preview-card" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs text-center hidden">
                <h3 class="font-bold text-xs uppercase tracking-wider text-outline mb-3">{{ __('admin.sample_vietqr_preview') }}</h3>
                <div class="inline-block p-3 bg-white rounded-xl border border-outline-variant shadow-xs">
                    <img id="qr-image" src="" alt="{{ __('admin.sample_vietqr_preview') }}" loading="lazy" class="w-48 h-48 mx-auto object-contain">
                </div>
                <div id="qr-details" class="mt-3 text-xs text-secondary font-mono"></div>
                <p class="text-[11px] text-outline mt-1">{{ __('admin.sample_qr_note') }}</p>
            </div>
        </div>
    </div>
</x-admin.layout>
