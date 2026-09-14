<x-admin.layout :title="__('admin.audit_trail_title')" active="audit" :room="$room">
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
                <span class="text-primary font-bold">{{ __('admin.audit_trail_title') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.audit_trail_title') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-secondary text-xs font-semibold border border-outline-variant">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span>{{ __('admin.immutable_audit') }}</span>
            </span>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <form method="GET" action="{{ route('admin.audit.page', $room) }}" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Event Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.filter_event') }}</label>
                <select name="event" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                    <option value="">{{ __('admin.all_events') }}</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev }}" {{ ($filters['event'] ?? '') === $ev ? 'selected' : '' }}>{{ $ev }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Target Object Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.filter_target') }}</label>
                <select name="target_type" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                    <option value="">{{ __('admin.all_targets') }}</option>
                    @foreach($targetTypes as $tt)
                        <option value="{{ $tt }}" {{ ($filters['target_type'] ?? '') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actor Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.filter_actor') }}</label>
                <input type="text" name="actor" value="{{ $filters['actor'] ?? '' }}" placeholder="{{ __('admin.filter_actor_placeholder') }}" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
            </div>

            <!-- Date From Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.from_date') }}</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
            </div>

            <!-- Date To Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.to_date') }}</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/40">
            <a href="{{ route('admin.audit.page', $room) }}" class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant rounded text-xs font-semibold no-underline transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">refresh</span>
                <span>{{ __('admin.filter_reset') }}</span>
            </a>
            <button type="submit" class="px-4 py-1.5 bg-primary hover:bg-primary/90 text-on-primary rounded text-xs font-semibold shadow-xs transition-colors flex items-center gap-1 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">filter_alt</span>
                <span>{{ __('admin.filter_apply') }}</span>
            </button>
        </div>
    </form>

    <!-- Audit Logs Table Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-outline font-mono uppercase text-[11px] border-b border-outline-variant">
                        <th class="py-3 px-4">{{ __('admin.timestamp') }}</th>
                        <th class="py-3 px-4">{{ __('admin.event_name') }}</th>
                        <th class="py-3 px-4">{{ __('admin.target_object') }}</th>
                        <th class="py-3 px-4">{{ __('admin.actor') }}</th>
                        <th class="py-3 px-4">{{ __('admin.ip_address') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.view_json') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-surface-container-low/50 transition-colors font-mono">
                            <td class="py-3.5 px-4 text-secondary">
                                {{ $log->created_at ? $log->created_at->format('H:i:s d/m/Y') : 'N/A' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface font-bold text-[11px] border border-outline-variant">
                                    {{ $log->event }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-on-surface">
                                <span class="font-semibold">{{ $log->target_type }}</span>
                                <span class="text-outline">#{{ $log->target_id }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-sans font-semibold text-on-surface">
                                {{ $log->actor_type }} #{{ $log->actor_id }}
                            </td>
                            <td class="py-3.5 px-4 text-outline text-[11px]">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-sans">
                                <button type="button" onclick="viewAuditPayload(@json($log))" class="px-2 py-1 bg-surface-container hover:bg-surface-container-high rounded text-[11px] font-semibold text-on-surface border border-outline-variant transition-colors">
                                    {{ __('admin.view_json') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-outline font-sans">
                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-1">history_toggle_off</span>
                                <p class="text-xs">No audit records found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="p-4 border-t border-outline-variant">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Payload Inspector Modal -->
    <div id="payload-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div id="payload-backdrop" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-xl bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant mb-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">data_object</span>
                    <h3 class="font-bold text-base text-on-surface" id="payload-title">{{ __('admin.audit_trail_title') }}</h3>
                </div>
                <button type="button" onclick="closePayloadModal()" class="text-outline hover:text-on-surface">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <pre id="payload-content" class="p-4 bg-surface-container-low rounded-lg border border-outline-variant font-mono text-xs overflow-x-auto text-on-surface"></pre>
        </div>
    </div>
</x-admin.layout>
