<x-admin.layout :title="__('admin.audit_trail_title')" active="audit" :room="$room">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.audit_trail_title') }}</h1>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <form id="audit-filter-form" data-skeleton-on-submit method="GET" action="{{ route('admin.audit.page', $room) }}" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <!-- Event Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.filter_event') }}</label>
                <select name="event" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                    <option value="">{{ __('admin.all_events') }}</option>
                    @foreach($events as $ev)
                        @php
                            $eventKey = 'admin.audit_event_' . str_replace('.', '_', $ev);
                            $eventLabel = __($eventKey);
                        @endphp
                        <option value="{{ $ev }}" {{ ($filters['event'] ?? '') === $ev ? 'selected' : '' }}>{{ $eventLabel === $eventKey ? $ev : $eventLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Target Object Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.filter_target') }}</label>
                <select name="target_type" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                    <option value="">{{ __('admin.all_targets') }}</option>
                    @foreach($targetTypes as $tt)
                        @php
                            $targetKey = 'admin.audit_target_' . $tt;
                            $targetLabel = __($targetKey);
                        @endphp
                        <option value="{{ $tt }}" {{ ($filters['target_type'] ?? '') === $tt ? 'selected' : '' }}>{{ $targetLabel === $targetKey ? $tt : $targetLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actor Filter -->
            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.filter_actor') }}</label>
                <input type="text" name="actor" value="{{ $filters['actor'] ?? '' }}" placeholder="{{ __('admin.filter_actor_placeholder') }}" class="w-full h-9 px-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface">
            </div>

            <div>
                <label class="block text-[11px] font-mono uppercase text-outline font-semibold mb-1">{{ __('admin.date_range') }}</label>
                <x-admin.date-range-filter id="audit-date-range" :date-from="$filters['date_from'] ?? ''" :date-to="$filters['date_to'] ?? ''" form-id="audit-filter-form" :full-width="true" />
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/40">
            <a href="{{ route('admin.audit.page', $room) }}" data-audit-filter-reset class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant rounded text-xs font-semibold no-underline transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]" data-reset-icon>refresh</span>
                <span data-reset-label>{{ __('admin.filter_reset') }}</span>
            </a>
            <button type="submit" data-icon-only data-tooltip="{{ __('admin.filter_apply') }}" aria-label="{{ __('admin.filter_apply') }}" class="text-xs font-semibold h-8.5 w-8.5 shrink-0 bg-primary hover:bg-primary/90 text-on-primary rounded shadow-xs transition-colors inline-flex items-center justify-center cursor-pointer">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">filter_alt</span>
            </button>
            <x-admin.reload-button :compact="true" />
        </div>
    </form>

    <!-- Audit Logs Table Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            {{-- Compact fixed-width columns; the event column has no width so it takes all remaining space. --}}
            <table data-skeleton="table" class="table-colgroup w-full min-w-[50rem] table-fixed text-left text-xs border-collapse">
                <colgroup>
                    <col class="w-24">
                    <col>
                    <col class="w-56">
                    <col class="w-28">
                    <col class="w-20">
                </colgroup>
                <thead>
                    <tr class="bg-surface-container-low text-outline font-mono uppercase text-[11px] border-b border-outline-variant">
                        <th class="py-3 px-3 whitespace-nowrap">{{ __('admin.timestamp') }}</th>
                        <th class="py-3 px-3">{{ __('admin.event_name') }}</th>
                        <th class="py-3 px-3">{{ __('admin.actor') }}</th>
                        <th class="py-3 px-3 whitespace-nowrap">{{ __('admin.ip_address') }}</th>
                        <th class="py-3 px-3 text-center whitespace-nowrap">{{ __('admin.details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50">
                    @forelse($logs as $log)
                        @php
                            $auditEventKey = 'admin.audit_event_' . str_replace('.', '_', $log->event);
                            $auditEventLabel = __($auditEventKey);
                            if ($auditEventLabel === $auditEventKey) $auditEventLabel = $log->event;
                            $auditTargetKey = 'admin.audit_target_' . $log->target_type;
                            $auditTargetLabel = __($auditTargetKey);
                            if ($auditTargetLabel === $auditTargetKey) $auditTargetLabel = $log->target_type;
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="py-3.5 px-3 text-secondary whitespace-nowrap font-mono">
                                @if($log->created_at)
                                    <span class="block font-semibold text-on-surface">{{ $log->created_at->format('H:i') }}</span>
                                    <span class="block text-[10px] text-outline">{{ $log->created_at->format('d/m/Y') }}</span>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-surface-container text-on-surface font-bold text-xs border border-outline-variant font-mono">
                                    {{ $auditEventLabel }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 font-semibold text-on-surface truncate" title="{{ $log->actor_type }} #{{ $log->actor_id }}">
                                {{ $log->actor_type }} #{{ $log->actor_id }}
                            </td>
                            <td class="py-3.5 px-3 font-mono text-outline text-[11px] whitespace-nowrap truncate" title="{{ $log->ip_address ?: '127.0.0.1' }}">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <button type="button"
                                    data-audit-payload="{{ json_encode($log, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" 
                                    data-audit-event-label="{{ $auditEventLabel }}" 
                                    data-audit-target-label="{{ $auditTargetLabel }}"
                                    data-audit-time="{{ $log->created_at ? $log->created_at->format('H:i:s d/m/Y') : 'N/A' }}"
                                    class="inline-flex h-8 w-8 items-center justify-center bg-surface-container hover:bg-surface-container-high rounded-lg text-on-surface border border-outline-variant transition-colors cursor-pointer shadow-xs"
                                    title="{{ __('admin.details') }}" aria-label="{{ __('admin.details') }}">
                                    <span class="material-symbols-outlined text-[16px] text-primary">visibility</span>
                                    <span class="sr-only">{{ __('admin.details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-outline">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">history_toggle_off</span>
                                    <p class="font-medium text-sm">{{ __('admin.no_audit_records') }}</p>
                                </div>
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
    <div id="payload-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="payload-title">
        <div id="payload-backdrop" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-2xl bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto space-y-5">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-2xl">manage_search</span>
                    <div>
                        <h3 class="font-bold text-base text-on-surface flex items-center gap-2" id="payload-title">
                            {{ __('admin.audit_trail_title') }}
                        </h3>
                    </div>
                </div>
                <button type="button" data-close-audit-payload class="w-8 h-8 rounded-lg flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- Section 1: Overview Information -->
            <div class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-4 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-outline flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[15px] text-primary">info</span>
                    <span>{{ __('admin.audit_overview') }}</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-outline text-[11px] block">{{ __('admin.timestamp') }}</span>
                        <div class="flex items-center gap-1.5 font-mono text-on-surface mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-secondary">schedule</span>
                            <span id="modal-audit-time" class="font-medium">-</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-outline text-[11px] block">{{ __('admin.event_name') }}</span>
                        <div class="mt-0.5">
                            <span id="modal-audit-event" class="inline-block px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20 font-bold text-[11px]">-</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-outline text-[11px] block">{{ __('admin.target_object') }}</span>
                        <div class="flex items-center gap-1.5 font-mono text-on-surface mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-outline">category</span>
                            <span id="modal-audit-target-label" class="font-semibold">-</span>
                            <span id="modal-audit-target-id" class="text-primary font-bold"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-outline text-[11px] block">{{ __('admin.actor') }}</span>
                        <div class="flex items-center gap-1.5 font-mono text-on-surface mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-outline">person</span>
                            <span id="modal-audit-actor" class="font-semibold">-</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-outline text-[11px] block">{{ __('admin.ip_address') }}</span>
                        <div class="flex items-center gap-1.5 font-mono text-on-surface mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-outline">router</span>
                            <span id="modal-audit-ip">-</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-outline text-[11px] block">{{ __('admin.device_uuid') }}</span>
                        <div class="flex items-center gap-1.5 font-mono text-on-surface mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-outline">devices</span>
                            <span id="modal-audit-device" class="text-outline text-[11px] truncate" title="">-</span>
                        </div>
                    </div>
                </div>
                <div class="pt-2 border-t border-outline-variant/40 text-xs">
                    <span class="text-outline text-[11px] block mb-0.5">{{ __('admin.user_agent') }}</span>
                    <span id="modal-audit-user-agent" class="font-mono text-[11px] text-on-surface/80 break-all leading-relaxed block">-</span>
                </div>
            </div>

            <!-- Section 2: Data Changes & Metadata -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-outline flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[15px] text-primary">swap_horiz</span>
                    <span>{{ __('admin.audit_changes') }}</span>
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Before Data -->
                    <div class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-3">
                        <div class="flex items-center gap-1.5 mb-2 text-xs font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-[15px] text-amber-500">history</span>
                            <span>{{ __('admin.before_data') }}</span>
                        </div>
                        <pre id="modal-audit-before" class="p-2.5 bg-surface-container-lowest rounded-lg border border-outline-variant font-mono text-[11px] overflow-x-auto text-on-surface max-h-48 leading-relaxed"></pre>
                        <p id="modal-audit-before-empty" class="text-xs text-outline italic py-2 hidden">{{ __('admin.no_data_recorded') }}</p>
                    </div>

                    <!-- After Data -->
                    <div class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-3">
                        <div class="flex items-center gap-1.5 mb-2 text-xs font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-[15px] text-emerald-500">update</span>
                            <span>{{ __('admin.after_data') }}</span>
                        </div>
                        <pre id="modal-audit-after" class="p-2.5 bg-surface-container-lowest rounded-lg border border-outline-variant font-mono text-[11px] overflow-x-auto text-on-surface max-h-48 leading-relaxed"></pre>
                        <p id="modal-audit-after-empty" class="text-xs text-outline italic py-2 hidden">{{ __('admin.no_data_recorded') }}</p>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-3">
                    <div class="flex items-center gap-1.5 mb-2 text-xs font-semibold text-on-surface">
                        <span class="material-symbols-outlined text-[15px] text-primary">data_array</span>
                        <span>{{ __('admin.metadata') }}</span>
                    </div>
                    <pre id="modal-audit-metadata" class="p-2.5 bg-surface-container-lowest rounded-lg border border-outline-variant font-mono text-[11px] overflow-x-auto text-on-surface max-h-48 leading-relaxed"></pre>
                    <p id="modal-audit-metadata-empty" class="text-xs text-outline italic py-2 hidden">{{ __('admin.no_data_recorded') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-admin.layout>
