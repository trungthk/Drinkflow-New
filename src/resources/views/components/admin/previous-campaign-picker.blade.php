{{--
    Closed campaigns whose menu can be copied into the campaign form, newest first.
    The first page is rendered on the server; "Xem thêm" appends the next pages from
    admin.campaigns.previous-menus. Relies on the parent campaignCreateComponent scope for
    selectedPreviousCampaignId / loadPreviousCampaign().
--}}
@props(['room', 'campaigns', 'hasMore' => false, 'excludeCampaignId' => null])

@if($campaigns->isNotEmpty())
<div class="space-y-3"
     x-data="{
        morePrevious: [],
        previousHasMore: {{ Js::from((bool) $hasMore) }},
        previousLoading: false,
        previousError: false,
        async loadMorePrevious() {
            if (this.previousLoading || !this.previousHasMore) return;
            this.previousLoading = true;
            this.previousError = false;
            try {
                const params = new URLSearchParams({ offset: String({{ $campaigns->count() }} + this.morePrevious.length) });
                @if($excludeCampaignId)
                params.set('exclude', '{{ (int) $excludeCampaignId }}');
                @endif
                const response = await fetch({{ Js::from(route('admin.campaigns.previous-menus', $room)) }} + '?' + params.toString(), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error();
                const payload = await response.json();
                const known = new Set({{ Js::from($campaigns->pluck('id')->values()) }}.concat(this.morePrevious.map((campaign) => campaign.id)));
                this.morePrevious.push(...(payload.data || []).filter((campaign) => !known.has(campaign.id)));
                this.previousHasMore = Boolean(payload.meta?.has_more);
            } catch (error) {
                this.previousError = true;
            } finally {
                this.previousLoading = false;
            }
        },
     }">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-72 overflow-y-auto pr-1" data-previous-campaign-list>
        @foreach($campaigns as $prev)
        <div class="p-3 min-w-0 rounded-lg border transition-all cursor-pointer flex flex-col justify-between"
             :class="selectedPreviousCampaignId === {{ $prev->id }} ? 'border-primary bg-primary/5 ring-1 ring-primary/40' : 'border-outline-variant bg-surface hover:border-primary'"
             :aria-pressed="selectedPreviousCampaignId === {{ $prev->id }} ? 'true' : 'false'"
             @click="loadPreviousCampaign({{ $prev->toJson() }})">
            <div class="font-bold text-xs text-on-surface flex items-center gap-1.5 min-w-0">
                <span x-show="selectedPreviousCampaignId === {{ $prev->id }}" x-cloak
                    class="material-symbols-outlined text-[16px] text-primary shrink-0"
                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
                <span class="truncate" title="{{ $prev->name }}">{{ $prev->name }}</span>
            </div>
            <div class="text-[11px] text-outline mt-1 truncate" title="{{ $prev->restaurant }}">{{ $prev->restaurant }}</div>
            <div class="mt-2 flex items-center justify-between gap-2">
                <div class="text-[10px] font-mono text-primary font-semibold flex items-center gap-1 min-w-0">
                    <span class="material-symbols-outlined text-[12px] shrink-0">history</span>
                    <span class="truncate">{{ $prev->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-surface-container-high text-outline whitespace-nowrap shrink-0">{{ $prev->items->count() }} {{ __('admin.items_unit') }}</span>
            </div>
        </div>
        @endforeach

        {{-- Cards appended by "Xem thêm"; same layout as the server-rendered cards above. --}}
        <template x-for="prev in morePrevious" :key="prev.id">
            <div class="p-3 min-w-0 rounded-lg border transition-all cursor-pointer flex flex-col justify-between"
                 :class="selectedPreviousCampaignId === prev.id ? 'border-primary bg-primary/5 ring-1 ring-primary/40' : 'border-outline-variant bg-surface hover:border-primary'"
                 :aria-pressed="selectedPreviousCampaignId === prev.id ? 'true' : 'false'"
                 @click="loadPreviousCampaign(prev)">
                <div class="font-bold text-xs text-on-surface flex items-center gap-1.5 min-w-0">
                    <span x-show="selectedPreviousCampaignId === prev.id" x-cloak
                        class="material-symbols-outlined text-[16px] text-primary shrink-0"
                        style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <span class="truncate" :title="prev.name" x-text="prev.name"></span>
                </div>
                <div class="text-[11px] text-outline mt-1 truncate" :title="prev.restaurant" x-text="prev.restaurant"></div>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <div class="text-[10px] font-mono text-primary font-semibold flex items-center gap-1 min-w-0">
                        <span class="material-symbols-outlined text-[12px] shrink-0">history</span>
                        <span class="truncate" x-text="prev.created_label"></span>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-surface-container-high text-outline whitespace-nowrap shrink-0"><span x-text="(prev.items || []).length"></span> {{ __('admin.items_unit') }}</span>
                </div>
            </div>
        </template>
    </div>

    <p x-show="previousError" x-cloak class="text-xs text-error" role="alert">{{ __('admin.previous_campaigns_load_failed') }}</p>

    <div x-show="previousHasMore" @unless($hasMore) x-cloak @endunless class="flex justify-center">
        <button type="button" data-previous-campaign-load-more @click="loadMorePrevious()" :disabled="previousLoading"
            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-xs font-semibold text-on-surface transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-wait">
            <span x-show="!previousLoading" class="material-symbols-outlined text-[16px]">expand_more</span>
            <span x-show="previousLoading" x-cloak class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
            <span x-show="!previousLoading">{{ __('admin.previous_campaigns_load_more') }}</span>
            <span x-show="previousLoading" x-cloak>{{ __('admin.previous_campaigns_loading') }}</span>
        </button>
    </div>
</div>
@else
<div class="flex flex-col items-center justify-center py-10 px-4 rounded-xl border border-dashed border-outline-variant bg-surface-container-low/30 text-center">
    <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">history</span>
    <p class="text-xs text-outline font-medium max-w-md">{{ __('admin.no_previous_campaigns') }}</p>
</div>
@endif
