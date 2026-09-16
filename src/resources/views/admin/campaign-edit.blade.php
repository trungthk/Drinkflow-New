@php
    $campaignStatusValue = $campaign->status instanceof \BackedEnum ? $campaign->status->value : (string) $campaign->status;
    $initialCampaignData = [
        'id' => $campaign->id,
        'name' => $campaign->name,
        'restaurant' => $campaign->restaurant,
        'deadline' => $campaign->deadline?->format('Y-m-d\TH:i') ?? '',
        'payment_account_id' => $campaign->payment_account_id ? (string) $campaign->payment_account_id : '',
        'description' => $campaign->description ?? '',
        'sponsor_type' => $campaign->sponsor_type ?? 'none',
        'sponsor_description' => $campaign->sponsor_description ?? '',
        'max_budget' => $campaign->max_budget ?? $maxBudget,
        'flat_price' => $campaign->flat_price ?? '',
        'status' => $campaignStatusValue,
        'sponsor_allocations' => $campaign->sponsor_allocations ?? [],
        'items' => $campaign->items->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'price' => (int) $item->base_price,
            'category' => $item->category ?? 'Khác',
            'description' => $item->description ?? '',
            'image_url' => $item->image_url ?? '',
            'toppings' => $item->toppings->map(fn ($top) => [
                'id' => $top->id,
                'name' => $top->name,
                'price' => (int) $top->price,
            ])->values()->all(),
            'options' => $item->sizes->map(fn ($size) => [
                'id' => $size->id,
                'name' => $size->name,
                'price_delta' => (int) $size->price_delta,
            ])->values()->all(),
        ])->values()->all(),
    ];
@endphp

<x-admin.layout :title="__('admin.edit_campaign')" active="campaigns" :room="$room">
<div id="campaign-edit-page"
     class="w-full space-y-6"
     data-is-edit="true"
     data-submit-method="PATCH"
     data-budget-error="{{ __('admin.campaign_budget_exceeds_limit', ['limit' => ':limit']) }}"
     data-sponsor-percentage-error="{{ __('admin.sponsor_percentage_total_invalid') }}"
     data-submit-url="{{ route('admin.campaigns.update', [$room, $campaign]) }}"
     data-image-upload-url="{{ route('admin.campaigns.menu-images.store', $room) }}"
     x-data="campaignCreateComponent(
        { max_budget: {{ (int) $maxBudget }} },
        @js($roomUsers->map(fn ($roomUser) => [
            'id' => $roomUser->id,
            'name' => $roomUser->globalUser?->name ?? $roomUser->display_name,
            'user_code' => $roomUser->user_code,
        ])->values()),
        @js($initialCampaignData)
     )">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-outline-variant">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.campaigns.page', $room) }}" class="w-9 h-9 rounded-lg border border-outline-variant flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold text-on-surface tracking-tight flex items-center gap-2">
                    {{ __('admin.edit_campaign') }}: <span class="text-primary">{{ $campaign->name }}</span>
                </h1>
                <p class="text-xs text-outline">{{ __('admin.edit_campaign_subtitle', ['room' => $room->name]) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <a href="{{ route('admin.campaigns.show', [$room, $campaign]) }}?view=detail" class="px-4 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1.5 no-underline">
                <span class="material-symbols-outlined text-[16px]">visibility</span>
                <span>{{ __('admin.view_campaign_details') }}</span>
            </a>
            <button type="button" @click="saveChanges()" :disabled="submitting" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50">
                <span x-show="submitting" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
                <span x-show="!submitting" class="material-symbols-outlined text-[16px]">save</span>
                <span x-text="submitting ? '{{ __('admin.processing') }}' : '{{ __('admin.save_changes') }}'"></span>
            </button>
        </div>
    </div>

    <!-- Main Bento Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Form Info + Menu Builder -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card 1: Basic Campaign Meta -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-2 border-b border-outline-variant/60 pb-2.5">
                    <span class="material-symbols-outlined text-[18px] text-primary">edit_note</span>
                    {{ __('admin.campaign_info') }}
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.campaign_name') }} <span class="text-error">*</span></label>
                        <input type="text" x-model="form.name" placeholder="{{ __('admin.campaign_name_example') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.restaurant_brand') }} <span class="text-error">*</span></label>
                        <input type="text" x-model="form.restaurant" placeholder="{{ __('admin.restaurant_example') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <div data-deadline-payment-grid class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.order_deadline') }}</label>
                        <input type="datetime-local" x-model="form.deadline" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.room_vietqr_account') }}</label>
                        <select x-model="form.payment_account_id" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                            <option value="">-- {{ __('admin.default_badge') }} Room --</option>
                            @foreach($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }} ({{ $account->bank_name }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.campaign_desc_label') }}</label>
                    <textarea x-model="form.description" rows="2" placeholder="{{ __('admin.campaign_desc_placeholder') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"></textarea>
                </div>
            </div>

            <!-- Card 2: Multi-Source Menu Builder -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-outline-variant/60 pb-2.5">
                    <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">restaurant_menu</span>
                        {{ __('admin.menu_and_items') }}
                    </h2>
                    <!-- Tab Switcher -->
                    <div class="flex items-center gap-1 bg-surface-container-low p-1 rounded-lg border border-outline-variant/60 text-xs">
                        <button type="button" @click="menuTab = 'reuse'" :class="menuTab === 'reuse' ? 'bg-primary font-bold text-white shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_previous') }}
                        </button>
                        <button type="button" @click="menuTab = 'json'" :class="menuTab === 'json' ? 'bg-primary font-bold text-white shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_json') }}
                        </button>
                        <button type="button" @click="menuTab = 'crawler'" :class="menuTab === 'crawler' ? 'bg-primary font-bold text-white shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_crawler') }}
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Previous Campaign -->
                <div x-show="menuTab === 'previous'" x-cloak class="space-y-3">
                    <div class="text-xs text-outline">{{ __('admin.copy_previous_menu_desc') }}</div>
                    @if($previousCampaigns->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                        @foreach($previousCampaigns as $prev)
                        <div class="p-3 rounded-lg border border-outline-variant bg-surface hover:border-primary transition-all cursor-pointer flex flex-col justify-between"
                             @click="loadPreviousCampaign({{ $prev->toJson() }})">
                            <div class="flex items-start justify-between gap-2">
                                <div class="font-bold text-xs text-on-surface">{{ $prev->name }}</div>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-surface-container-high text-outline">{{ $prev->items->count() }} {{ __('admin.items_unit') }}</span>
                            </div>
                            <div class="text-[11px] text-outline mt-1 truncate">{{ $prev->restaurant }}</div>
                            <div class="text-[10px] font-mono text-primary font-semibold mt-2 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">history</span>
                                {{ $prev->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-10 px-4 rounded-xl border border-dashed border-outline-variant bg-surface-container-low/30 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">history</span>
                        <p class="text-xs text-outline font-medium max-w-md">{{ __('admin.no_previous_campaigns') }}</p>
                    </div>
                    @endif
                </div>

                <!-- Tab 2: URL Crawler -->
                <div x-show="menuTab === 'crawler'" x-cloak class="space-y-3">
                    <div class="text-xs text-outline">{{ __('admin.crawler_desc') }}</div>
                    <div class="flex gap-2">
                        <input type="url" x-model="crawlerUrl" placeholder="{{ __('admin.crawler_url_placeholder') }}" class="flex-1 px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                        <button type="button" @click="previewCrawler()" :disabled="crawlerLoading || !crawlerUrl" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs font-semibold hover:bg-primary-container disabled:opacity-50 transition-colors flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]" :class="crawlerLoading ? 'animate-spin' : ''">sync</span>
                            <span>{{ __('admin.crawl_menu_btn') }}</span>
                        </button>
                    </div>
                    <div x-show="crawlerMessage" class="text-xs p-2.5 rounded bg-surface-container-low border border-outline-variant text-on-surface" x-text="crawlerMessage"></div>
                </div>

                <!-- Tab 3: JSON Schema -->
                <div x-show="menuTab === 'json'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline">{{ __('admin.json_import_desc') }}</span>
                        <button type="button" @click="loadSampleJson()" class="text-xs text-primary hover:underline font-medium">{{ __('admin.view_sample_json') }}</button>
                    </div>
                    <textarea x-model="rawJson" rows="6" placeholder="{{ __('admin.json_menu_placeholder') }}" class="w-full font-mono text-xs p-3 bg-surface border border-outline-variant rounded-lg text-on-surface focus:outline-none focus:border-primary"></textarea>
                    <button type="button" @click="importJson()" class="px-4 py-1.5 bg-primary/10 text-primary border border-primary/30 rounded text-xs font-semibold hover:bg-primary/20 transition-colors">
                        {{ __('admin.apply_json_to_menu') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Sponsorship & Cost Breakdown -->
        <div class="space-y-6">
            <!-- Card 3: Sponsorship Policy -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-2 border-b border-outline-variant/60 pb-2.5">
                    <span class="material-symbols-outlined text-[18px] text-primary">volunteer_activism</span>
                    {{ __('admin.sponsor_policy_title') }}
                </h2>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_type_label') }}</label>
                    <select x-model="form.sponsor_type" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                        <option value="none">{{ __('admin.sponsor_type_none') }}</option>
                        <option value="full">{{ __('admin.sponsor_type_full') }}</option>
                    </select>
                </div>

                <div x-show="form.sponsor_type === 'full'" x-cloak class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-on-surface">{{ __('admin.sponsor_users_label') }}</span>
                        <button type="button" @click="addSponsor()" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">add</span><span>{{ __('admin.add_sponsor') }}</span></button>
                    </div>
                    <template x-for="(sponsor, index) in sponsors" :key="index">
                        <div class="p-2.5 rounded-lg border border-outline-variant bg-surface space-y-2">
                            <div class="grid grid-cols-[1fr_auto] gap-2">
                                <div class="relative" @click.outside="sponsor.open = false">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-2 text-[16px] text-outline">search</span>
                                    <input type="text" x-model="sponsor.search" @focus="sponsor.open = true" @input="sponsor.open = true; sponsor.user_id = ''" placeholder="{{ __('admin.search_sponsor_placeholder') }}" autocomplete="off" class="w-full pl-8 pr-3 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs text-on-surface focus:outline-none focus:border-primary">
                                    <div x-show="sponsor.open" x-cloak class="absolute z-30 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-outline-variant bg-surface-container-lowest p-1 shadow-xl">
                                        <template x-for="user in filteredSponsorUsers(sponsor.search)" :key="user.id">
                                            <button type="button" @click="selectSponsor(sponsor, user)" class="flex w-full items-center justify-between gap-2 rounded-md px-2.5 py-2 text-left text-xs hover:bg-primary/5">
                                                <span class="truncate text-on-surface" x-text="user.name"></span>
                                                <span class="shrink-0 font-mono text-[10px] text-primary" x-text="user.user_code"></span>
                                            </button>
                                        </template>
                                        <p x-show="filteredSponsorUsers(sponsor.search).length === 0" class="px-2.5 py-3 text-center text-[11px] text-outline">{{ __('admin.no_sponsor_user_found') }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="removeSponsor(index)" class="p-1 text-error hover:bg-error-container/40 rounded"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                            </div>
                            <input type="number" min="0" max="100" step="0.01" x-model="sponsor.percentage" @input="clampSponsorPercentage(sponsor)" @blur="sponsor.percentage = sponsor.percentage === '' ? 0 : sponsor.percentage" placeholder="{{ __('admin.sponsor_percentage_placeholder') }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs font-mono text-on-surface">
                            <p class="text-[10px] text-outline">{{ __('admin.sponsor_percentage_range_hint') }}</p>
                        </div>
                    </template>
                    <p x-show="sponsors.length === 0" class="text-[11px] text-outline italic">{{ __('admin.no_sponsors_added') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.max_product_budget_ceiling') }}</label>
                    <div class="relative">
                        <input type="number" min="0" max="{{ $maxBudget }}" x-bind:max="campaignSettings.max_budget" x-model="form.max_budget" placeholder="0" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-8">
                        <span class="absolute right-3 top-2 text-xs text-outline font-mono">đ</span>
                    </div>
                    <p class="mt-1 text-[11px] text-outline">{{ __('admin.product_budget_limit_hint') }} <span class="font-mono font-semibold text-primary" x-text="formatVND(campaignSettings.max_budget)"></span></p>
                </div>
                <div x-show="form.sponsor_type !== 'none'">
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_description_label') }}</label>
                    <textarea x-model="form.sponsor_description" rows="2" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Selected menu section -->
    <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-outline-variant/60 pb-3">
            <div>
                <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary">fact_check</span>
                    {{ __('admin.selected_menu_preview') }}
                </h2>
                <p class="mt-1 text-[11px] text-outline"><span x-text="menuItems.length"></span> {{ __('admin.items_unit') }} · {{ __('admin.selected_menu_edit_hint') }}</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <div class="flex rounded-lg border border-outline-variant bg-surface-container-low p-1 text-xs">
                    <button type="button" @click="setMenuView('all')" :class="menuView === 'all' ? 'bg-primary text-white shadow-sm' : 'text-outline hover:text-on-surface'" class="rounded-md px-3 py-1.5 font-semibold transition-colors">{{ __('admin.menu_view_all') }}</button>
                    <button type="button" @click="setMenuView('category')" :class="menuView === 'category' ? 'bg-primary text-white shadow-sm' : 'text-outline hover:text-on-surface'" class="rounded-md px-3 py-1.5 font-semibold transition-colors">{{ __('admin.menu_view_category') }}</button>
                </div>
                <button type="button" @click="openAddItemModal()" class="px-3 py-2 bg-primary hover:bg-primary-container text-white rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    {{ __('admin.add_manual_item_button') }}
                </button>
            </div>
        </div>

        <div class="relative max-w-xl">
            <span class="material-symbols-outlined pointer-events-none absolute left-3 top-2.5 text-[18px] text-outline">search</span>
            <input type="text" data-search-debounce="300" :value="menuSearchInput" @input="updateMenuSearch($event.target.value)" placeholder="{{ __('admin.search_menu_item_placeholder') }}" class="w-full rounded-lg border border-outline-variant bg-surface py-2.5 pl-10 pr-9 text-xs text-on-surface focus:border-primary focus:outline-none">
            <button x-show="menuSearchInput" type="button" @click="updateMenuSearch('')" class="absolute right-2.5 top-2 text-outline hover:text-on-surface" title="{{ __('admin.clear_search') }}"><span class="material-symbols-outlined text-[18px]">close</span></button>
        </div>

        <div x-show="menuView === 'category'" x-cloak class="flex flex-wrap gap-2">
            <template x-for="category in menuCategories" :key="category">
                <button type="button" @click="selectedCategory = category" :class="selectedCategory === category ? 'border-primary bg-primary/10 text-primary font-bold' : 'border-outline-variant bg-surface text-outline hover:text-on-surface'" class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors">
                    <span x-text="category"></span>
                    <span class="rounded-full bg-surface-container-high px-1.5 py-0.5 font-mono text-[10px]" x-text="categoryItemCount(category)"></span>
                </button>
            </template>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            <template x-for="entry in visibleMenuItems" :key="entry.index">
                <article class="flex items-center gap-3 rounded-xl border border-outline-variant bg-surface p-3 hover:border-primary/40 transition-colors">
                    <img x-show="entry.item.image_url" :src="entry.item.image_url" :alt="entry.item.name || '{{ __('admin.item_image_alt') }}'" loading="lazy" onerror="this.style.display='none'" class="h-16 w-16 shrink-0 rounded-lg object-cover border border-outline-variant">
                    <div x-show="!entry.item.image_url" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg border border-outline-variant bg-surface-container-low text-outline">
                        <span class="material-symbols-outlined">restaurant</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-on-surface" x-text="entry.item.name"></h3>
                        <p class="mt-0.5 truncate text-[11px] text-outline" x-text="entry.item.category || '{{ __('admin.uncategorized') }}'"></p>
                        <p class="mt-1 font-mono text-xs font-bold text-primary" x-text="formatVND(entry.item.price)"></p>
                    </div>
                    <div class="flex shrink-0 flex-col gap-1">
                        <button type="button" @click="openEditItemModal(entry.index)" class="group/edit relative rounded-lg p-1.5 text-primary hover:bg-primary/10 transition-colors" title="{{ __('admin.edit_item') }}" aria-label="{{ __('admin.edit_item') }}">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                            <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/edit:opacity-100 group-focus-visible/edit:opacity-100">{{ __('admin.edit_item') }}</span>
                        </button>
                        <button type="button" @click="removeMenuItem(entry.index)" class="group/del relative rounded-lg p-1.5 text-error hover:bg-error-container/40 transition-colors" title="{{ __('admin.delete_item') }}" aria-label="{{ __('admin.delete_item') }}">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                            <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/del:opacity-100 group-focus-visible/del:opacity-100">{{ __('admin.delete_item') }}</span>
                        </button>
                    </div>
                </article>
            </template>
        </div>
        <div x-show="menuItems.length === 0" class="flex flex-col items-center justify-center py-12 px-4 rounded-xl border border-dashed border-outline-variant bg-surface-container-low/30 text-center">
            <span class="material-symbols-outlined text-4xl text-primary/60 mb-2">no_food</span>
            <p class="text-xs text-outline font-medium max-w-md">{{ __('admin.no_items_yet') }}</p>
        </div>
        <div x-show="menuItems.length > 0 && visibleMenuItems.length === 0" class="flex flex-col items-center justify-center py-10 px-4 rounded-xl border border-dashed border-outline-variant bg-surface-container-low/30 text-center">
            <span class="material-symbols-outlined text-3xl text-outline-variant mb-2">search_off</span>
            <p class="text-xs text-outline font-medium">{{ __('admin.no_menu_item_found') }}</p>
        </div>
    </section>

    <!-- Modal for Manual Add / Edit Item -->
    <div x-show="showAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-xl bg-surface-container-lowest border border-outline-variant p-5 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-base text-on-surface">
                    <span x-show="editingItemIndex === null">{{ __('admin.add_manual_item_title') }}</span>
                    <span x-show="editingItemIndex !== null">{{ __('admin.edit_menu_item_title') }}</span>
                </h3>
                <button type="button" @click="showAddItemModal = false; editingItemIndex = null" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
            </div>

            <div class="mb-4 flex rounded-lg border border-outline-variant bg-surface-container-low p-1 text-xs">
                <button type="button" @click="itemModalTab = 'basic'" :class="itemModalTab === 'basic' ? 'bg-primary text-on-primary shadow-sm' : 'text-outline hover:text-on-surface'" class="flex-1 rounded-md px-3 py-2 font-semibold transition-colors">{{ __('admin.item_tab_basic') }}</button>
                <button type="button" @click="itemModalTab = 'additional'" :class="itemModalTab === 'additional' ? 'bg-primary text-on-primary shadow-sm' : 'text-outline hover:text-on-surface'" class="flex-1 rounded-md px-3 py-2 font-semibold transition-colors">{{ __('admin.item_tab_additional') }}</button>
            </div>

            <div x-show="itemModalTab === 'basic'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_category_label') }}</label>
                    <input type="text" x-model="newItem.category" list="campaign-item-categories" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                    <datalist id="campaign-item-categories"><template x-for="category in itemCategories" :key="category"><option :value="category"></option></template></datalist>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_name_placeholder') }}</label>
                    <input type="text" x-model="newItem.name" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.price_vnd') }}</label>
                    <input type="number" min="0" x-model="newItem.price" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs font-mono text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_image_url_label') }}</label>
                    <input type="url" x-model="newItem.image_url" placeholder="https://example.com/image.jpg" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_image_upload_label') }}</label>
                    <input type="file" accept="image/jpeg,image/png,image/webp" @change="uploadManualImage($event)" :disabled="imageUploading" class="w-full text-xs text-outline file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-xs file:font-semibold file:text-on-primary">
                    <p x-show="imageUploading" class="mt-1 text-[11px] text-primary">{{ __('admin.image_uploading') }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_description_label') }}</label>
                    <textarea x-model="newItem.description" rows="2" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface"></textarea>
                </div>
            </div>

            <div x-show="itemModalTab === 'additional'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="rounded-lg border border-outline-variant p-3 space-y-2">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold">{{ __('admin.toppings_and_extras') }}</span><button type="button" @click="addTopping(newItem)" class="text-xs text-primary font-semibold">{{ __('admin.add_topping') }}</button></div>
                    <template x-for="(topping, index) in newItem.toppings" :key="index"><div class="grid grid-cols-[1fr_7rem_auto] gap-2"><input type="text" x-model="topping.name" placeholder="{{ __('admin.option_name_placeholder') }}" class="min-w-0 px-2 py-1.5 border border-outline-variant rounded text-xs"><input type="number" min="0" x-model="topping.price" placeholder="{{ __('admin.price_vnd') }}" class="min-w-0 px-2 py-1.5 border border-outline-variant rounded text-xs"><button type="button" @click="newItem.toppings.splice(index, 1)" class="text-error"><span class="material-symbols-outlined text-[16px]">close</span></button></div></template>
                </div>
                <div class="rounded-lg border border-outline-variant p-3 space-y-2">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold">{{ __('admin.sizes_and_options') }}</span><button type="button" @click="addOption(newItem)" class="text-xs text-primary font-semibold">{{ __('admin.add_option') }}</button></div>
                    <template x-for="(option, index) in newItem.options" :key="index"><div class="grid grid-cols-[1fr_7rem_auto] gap-2"><input type="text" x-model="option.name" placeholder="{{ __('admin.option_name_placeholder') }}" class="min-w-0 px-2 py-1.5 border border-outline-variant rounded text-xs"><input type="number" min="0" x-model="option.price_delta" placeholder="{{ __('admin.price_delta_placeholder') }}" class="min-w-0 px-2 py-1.5 border border-outline-variant rounded text-xs"><button type="button" @click="newItem.options.splice(index, 1)" class="text-error"><span class="material-symbols-outlined text-[16px]">close</span></button></div></template>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="showAddItemModal = false; editingItemIndex = null" :disabled="itemSubmitting" class="px-4 py-2 rounded-lg bg-surface-container text-on-surface text-xs font-semibold disabled:opacity-50">{{ __('admin.cancel') }}</button>
                <button type="button" @click="confirmAddItem()" :disabled="itemSubmitting || imageUploading" class="min-w-32 px-4 py-2 rounded-lg bg-primary text-on-primary text-xs font-semibold disabled:opacity-60 flex items-center justify-center gap-2">
                    <span x-show="itemSubmitting" class="material-symbols-outlined animate-spin text-[17px]">progress_activity</span>
                    <span x-show="!itemSubmitting && editingItemIndex === null">{{ __('admin.add_new_item_btn') }}</span>
                    <span x-show="!itemSubmitting && editingItemIndex !== null">{{ __('admin.save_item_changes') }}</span>
                    <span x-show="itemSubmitting">{{ __('admin.processing') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
</x-admin.layout>
