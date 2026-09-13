<x-admin.layout :title="__('admin.users_directory')" active="users" :room="$room">
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
                <span class="text-primary font-bold">{{ __('admin.users') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.users_directory') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-surface-container border border-outline-variant rounded text-xs font-semibold text-on-surface">
                {{ __('admin.sync_google_workspace') }}
            </span>
        </div>
    </div>

    <!-- Notice Notification Banner -->
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <!-- 4 Member KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.total_members') }}</span>
                <span class="material-symbols-outlined text-[20px] text-primary">group</span>
            </div>
            <div class="text-2xl font-bold font-mono text-on-surface mt-2">{{ $totalMembers ?? 0 }}</div>
            <div class="text-[11px] text-outline mt-1">{{ __('admin.kpi_total_members_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.active_members') }}</span>
                <span class="material-symbols-outlined text-[20px] text-emerald-600">verified_user</span>
            </div>
            <div class="text-2xl font-bold font-mono text-emerald-600 mt-2">{{ $activeMembers ?? 0 }}</div>
            <div class="text-[11px] text-emerald-700 mt-1">{{ __('admin.kpi_active_members_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.blocked_members') }}</span>
                <span class="material-symbols-outlined text-[20px] text-rose-600">block</span>
            </div>
            <div class="text-2xl font-bold font-mono text-rose-600 mt-2">{{ $blockedMembers ?? 0 }}</div>
            <div class="text-[11px] text-rose-700 mt-1">{{ __('admin.kpi_blocked_members_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.debt_members') }}</span>
                <span class="material-symbols-outlined text-[20px] text-amber-600">pending_actions</span>
            </div>
            <div class="text-2xl font-bold font-mono text-amber-600 mt-2">{{ $debtMembers ?? 0 }}</div>
            <div class="text-[11px] text-amber-700 mt-1">{{ __('admin.kpi_debt_members_desc') }}</div>
        </div>
    </div>

    <!-- Search & Filter Toolbar -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <x-admin.search-input id="user-search" placeholder="{{ __('admin.search_users_placeholder') }}" containerClass="relative w-full max-w-md" />
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button" data-status="all" class="user-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-primary text-on-primary transition-colors">{{ __('admin.filter_all') }}</button>
            <button type="button" data-status="active" class="user-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">Active</button>
            <button type="button" data-status="blocked" class="user-status-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">Blocked</button>
        </div>
    </div>

    <!-- Users Table Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-outline font-mono uppercase text-[11px] border-b border-outline-variant">
                        <th class="py-3 px-4">{{ __('admin.th_user_member') }}</th>
                        <th class="py-3 px-4">{{ __('admin.th_user_role') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_trusted_devices') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_orders_placed_count') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_status') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="users-tbody" class="divide-y divide-outline-variant/50">
                    @forelse($roomUsers as $ru)
                        @php
                            $name = $ru->globalUser?->name ?? $ru->display_name ?? 'Member #' . $ru->id;
                            $email = $ru->globalUser?->email ?? 'N/A';
                            $roleClass = match($ru->role) {
                                'owner' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
                                'admin' => 'bg-purple-50 text-purple-700 border-purple-200 font-semibold',
                                default => 'bg-surface-container text-secondary border-outline-variant'
                            };
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors" data-user-row data-status="{{ $ru->status }}" data-search="{{ strtolower($name . ' ' . $email . ' ' . $ru->user_code) }}">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-xs">
                                        {{ mb_substr($name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-on-surface text-sm">{{ $name }}</div>
                                        <div class="text-[11px] text-outline">{{ $email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] border {{ $roleClass }}">
                                    {{ ucfirst($ru->role) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono">
                                <button type="button" onclick="openDeviceTrustModal({{ $ru->id }}, '{{ addslashes($name) }}')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[11px] border border-outline-variant transition-colors" title="{{ __('admin.manage_devices') }}">
                                    <span class="material-symbols-outlined text-[14px] text-primary">devices</span>
                                    <span>{{ __('admin.devices_unit', ['count' => $ru->devices_count ?? 0]) }}</span>
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-on-surface">
                                {{ __('admin.orders_unit', ['count' => $ru->orders_count ?? 0]) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $ru->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                    {{ ucfirst($ru->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="openDeviceTrustModal({{ $ru->id }}, '{{ addslashes($name) }}')" class="p-1 text-secondary hover:text-primary rounded hover:bg-surface-container transition-colors" title="{{ __('admin.manage_devices') }}">
                                        <span class="material-symbols-outlined text-[16px]">security</span>
                                    </button>
                                    <button type="button" onclick="toggleUserStatus({{ $ru->id }}, '{{ $ru->status === 'active' ? 'blocked' : 'active' }}')" class="p-1 rounded hover:bg-surface-container transition-colors {{ $ru->status === 'active' ? 'text-secondary hover:text-rose-600' : 'text-emerald-600' }}" title="{{ $ru->status === 'active' ? __('admin.btn_block_user') : __('admin.btn_unblock_user') }}">
                                        <span class="material-symbols-outlined text-[16px]">{{ $ru->status === 'active' ? 'lock' : 'lock_open' }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-outline">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">group</span>
                                    <p class="font-medium text-sm">{{ __('admin.no_users_found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($roomUsers->hasPages())
            <div class="p-4 border-t border-outline-variant">
                {{ $roomUsers->links() }}
            </div>
        @endif
    </div>

    <!-- Device Trust Management Modal -->
    <div id="device-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div id="device-backdrop" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-xl bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">devices</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-on-surface" id="device-modal-title">{{ __('admin.manage_devices') }}</h3>
                        <p class="text-xs text-outline">{{ __('admin.device_fingerprint') }}</p>
                    </div>
                </div>
                <button type="button" onclick="closeDeviceModal()" class="text-outline hover:text-on-surface">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <div id="device-modal-body" class="space-y-3">
                <!-- Dynamically loaded -->
            </div>
        </div>
    </div>
</x-admin.layout>
