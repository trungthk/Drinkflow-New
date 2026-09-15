<x-admin.layout :title="__('admin.fast_create_campaign')" active="campaigns" :room="$room">
<div id="campaign-create-page" class="max-w-6xl mx-auto space-y-6" data-budget-error="{{ __('admin.campaign_budget_exceeds_limit', ['limit' => ':limit']) }}" x-data="campaignCreateComponent(@js($campaignDefaults))">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-outline-variant">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}" class="w-9 h-9 rounded-lg border border-outline-variant flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold text-on-surface tracking-tight flex items-center gap-2">
                    {{ __('admin.fast_create_campaign') }}
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-primary/10 text-primary uppercase border border-primary/20">{{ __('admin.fast_dispatcher_badge') }}</span>
                </h1>
                <p class="text-xs text-outline">{{ __('admin.campaign_create_subtitle', ['room' => $room->name]) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <button type="button" @click="saveDraft()" :disabled="submitting" class="px-4 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1.5 disabled:opacity-50">
                <span class="material-symbols-outlined text-[16px]">save</span>
                <span>{{ __('admin.save_draft') }}</span>
            </button>
            <button type="button" @click="publishCampaign()" :disabled="submitting" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50">
                <span class="material-symbols-outlined text-[16px]">rocket_launch</span>
                <span>{{ __('admin.publish_campaign') }}</span>
            </button>
        </div>
    </div>

    @if(false)
    <!-- Quick Presets Row -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-primary">storefront</span>
                {{ __('admin.quick_brand_presets') }}
            </span>
            <span class="text-[11px] text-outline">{{ __('admin.click_to_autofill_menu') }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
            <button type="button" @click="applyPreset('highlands')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Highlands Coffee</div>
                <div class="text-[10px] text-outline truncate">{{ __('admin.preset_highlands_description') }}</div>
            </button>
            <button type="button" @click="applyPreset('phuclong')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Phúc Long Tea</div>
                <div class="text-[10px] text-outline truncate">{{ __('admin.preset_phuclong_description') }}</div>
            </button>
            <button type="button" @click="applyPreset('gongcha')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Gong Cha</div>
                <div class="text-[10px] text-outline truncate">{{ __('admin.preset_gongcha_description') }}</div>
            </button>
            <button type="button" @click="applyPreset('tocotoco')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">TocoToco Tea</div>
                <div class="text-[10px] text-outline truncate">{{ __('admin.preset_tocotoco_description') }}</div>
            </button>
            <button type="button" @click="applyPreset('starbucks')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Starbucks</div>
                <div class="text-[10px] text-outline truncate">{{ __('admin.preset_starbucks_description') }}</div>
            </button>
            <button type="button" @click="applyPreset('comtam')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Cơm Tấm Phúc Lộc</div>
                <div class="text-[10px] text-outline truncate">{{ __('admin.preset_comtam_description') }}</div>
            </button>
        </div>
    </div>
    @endif

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
                        <input type="text" x-model="form.name" value="{{ $campaignDefaults['name'] }}" placeholder="{{ __('admin.campaign_name_example') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                        <p class="mt-1 text-[11px] text-outline">{{ __('admin.default_campaign_name_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.restaurant_brand') }} <span class="text-error">*</span></label>
                        <input type="text" x-model="form.restaurant" placeholder="{{ __('admin.restaurant_example') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.order_deadline') }}</label>
                        <div class="flex gap-2">
                            <input type="datetime-local" x-model="form.deadline" class="flex-1 px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                            <div class="flex gap-1 shrink-0">
                                <button type="button" @click="setDeadlineMinutes(30)" class="px-2 py-1 bg-surface-container-low border border-outline-variant rounded text-[11px] font-medium hover:border-primary hover:text-primary transition-colors">+30m</button>
                                <button type="button" @click="setDeadlineMinutes(60)" class="px-2 py-1 bg-surface-container-low border border-outline-variant rounded text-[11px] font-medium hover:border-primary hover:text-primary transition-colors">+60m</button>
                            </div>
                        </div>
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
                        <button type="button" @click="menuTab = 'reuse'" :class="menuTab === 'reuse' ? 'bg-surface-container-lowest font-bold text-primary shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_previous') }}
                        </button>
                        <button type="button" @click="menuTab = 'crawler'" :class="menuTab === 'crawler' ? 'bg-surface-container-lowest font-bold text-primary shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_crawler') }}
                        </button>
                        <button type="button" @click="menuTab = 'json'" :class="menuTab === 'json' ? 'bg-surface-container-lowest font-bold text-primary shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_json') }}
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Reuse Previous Campaign -->
                <div x-show="menuTab === 'reuse'" x-cloak class="space-y-3">
                    <div class="text-xs text-outline">{{ __('admin.copy_previous_menu_desc') }}</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                        @forelse($previousCampaigns as $prev)
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
                        @empty
                        <div class="col-span-2 text-center py-6 text-xs text-outline italic">{{ __('admin.no_previous_campaigns') }}</div>
                        @endforelse
                    </div>
                </div>

                <!-- Legacy manual editor is intentionally unavailable; menus are created from one of the three sources above. -->
                <div x-show="false" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline">{{ __('admin.menu_items_count', ['count' => '']) }}<span x-text="menuItems.length"></span>:</span>
                        <button type="button" @click="openAddItemModal()" class="px-3 py-1 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/30 rounded text-xs font-semibold transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            {{ __('admin.add_new_item_btn') }}
                        </button>
                    </div>

                    <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
                        <template x-for="(item, index) in menuItems" :key="index">
                            <div class="p-3 rounded-lg border border-outline-variant/80 bg-surface space-y-2">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <div class="col-span-6 sm:col-span-5">
                                        <input type="text" x-model="item.name" placeholder="{{ __('admin.item_name_placeholder') }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs text-on-surface focus:outline-none focus:border-primary">
                                    </div>
                                    <div class="col-span-4 sm:col-span-3">
                                        <div class="relative">
                                            <input type="number" x-model="item.price" placeholder="{{ __('admin.price_vnd') }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-6">
                                            <span class="absolute right-2 top-1.5 text-[10px] text-outline font-mono">đ</span>
                                        </div>
                                    </div>
                                    <div class="col-span-10 sm:col-span-3">
                                        <input type="text" x-model="item.category" placeholder="{{ __('admin.category_placeholder') }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs text-on-surface focus:outline-none focus:border-primary">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1 text-right">
                                        <button type="button" @click="removeMenuItem(index)" class="p-1.5 text-error hover:bg-error-container/40 rounded transition-colors" title="{{ __('admin.delete_item') }}">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </div>
                                <div x-show="form.sponsor_type === 'per_item'" class="flex items-center gap-2 text-[11px] text-outline">
                                    <span>{{ __('admin.sponsor_per_item_amount') }}</span>
                                    <input type="number" min="0" x-model="item.sponsor_amount" class="w-28 px-2 py-1 bg-surface-container-lowest border border-outline-variant rounded font-mono text-on-surface">
                                </div>
                            </div>
                        </template>

                        <div x-show="menuItems.length === 0" class="text-center py-6 text-xs text-outline italic">
                            {{ __('admin.no_items_yet') }}
                        </div>
                    </div>
                </div>

                <!-- Tab 3: URL Crawler -->
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

                <!-- Tab 4: JSON Schema -->
                <div x-show="menuTab === 'json'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline">{{ __('admin.json_import_desc') }}</span>
                        <button type="button" @click="loadSampleJson()" class="text-xs text-primary hover:underline font-medium">{{ __('admin.view_sample_json') }}</button>
                    </div>
                    <div x-show="menuItems.length > 0" x-cloak class="rounded-lg border border-primary/20 bg-primary/5 p-3 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-on-surface">{{ __('admin.selected_menu_preview') }}</span>
                            <span class="font-mono text-primary" x-text="menuItems.length + ' {{ __('admin.items_unit') }}'"></span>
                        </div>
                        <div class="max-h-28 overflow-y-auto space-y-1">
                            <template x-for="item in menuItems" :key="item.name + item.price">
                                <div class="flex justify-between gap-3 text-[11px] text-outline">
                                    <span class="truncate" x-text="item.name"></span>
                                    <span class="font-mono shrink-0" x-text="formatVND(item.price)"></span>
                                </div>
                            </template>
                        </div>
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

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-on-surface">{{ __('admin.sponsor_users_label') }}</span>
                        <button type="button" @click="addSponsor()" class="text-xs text-primary font-semibold hover:underline">+ {{ __('admin.add_sponsor') }}</button>
                    </div>
                    <template x-for="(sponsor, index) in sponsors" :key="index">
                        <div class="p-2.5 rounded-lg border border-outline-variant bg-surface space-y-2">
                            <div class="grid grid-cols-[1fr_auto] gap-2">
                                <select x-model="sponsor.user_id" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs text-on-surface">
                                    <option value="">{{ __('admin.select_sponsor_user') }}</option>
                                    @foreach($roomUsers as $roomUser)
                                        <option value="{{ $roomUser->id }}">{{ $roomUser->globalUser?->name ?? $roomUser->display_name }} ({{ $roomUser->user_code }})</option>
                                    @endforeach
                                </select>
                                <button type="button" @click="removeSponsor(index)" class="p-1 text-error hover:bg-error-container/40 rounded"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <select x-model="sponsor.type" class="px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs text-on-surface">
                                    <option value="per_item">{{ __('admin.sponsor_type_per_item') }}</option>
                                    <option value="budget">{{ __('admin.sponsor_type_budget') }}</option>
                                    <option value="full">{{ __('admin.sponsor_type_full') }}</option>
                                </select>
                                <input type="number" min="0" x-model="sponsor.amount" placeholder="{{ __('admin.sponsor_amount_placeholder') }}" class="px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs font-mono text-on-surface">
                            </div>
                            <input type="text" x-model="sponsor.description" placeholder="{{ __('admin.sponsor_description_label') }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant rounded text-xs text-on-surface">
                        </div>
                    </template>
                    <p x-show="sponsors.length === 0" class="text-[11px] text-outline italic">{{ __('admin.no_sponsors_added') }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_name_label') }}</label>
                    <input type="text" x-model="form.sponsor_name" placeholder="{{ __('admin.sponsor_example') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_type_label') }}</label>
                    <select x-model="form.sponsor_type" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                        <option value="none">{{ __('admin.sponsor_type_none') }}</option>
                        <option value="per_item">{{ __('admin.sponsor_type_per_item') }}</option>
                        <option value="budget">{{ __('admin.sponsor_type_budget') }}</option>
                        <option value="full">{{ __('admin.sponsor_type_full') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.max_budget_ceiling') }}</label>
                    <div class="relative">
                        <input type="number" min="0" max="{{ $campaignDefaults['max_budget'] }}" x-bind:max="campaignSettings.max_budget" x-model="form.max_budget" value="{{ $campaignDefaults['max_budget'] }}" placeholder="0" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-8">
                        <span class="absolute right-3 top-2 text-xs text-outline font-mono">đ</span>
                    </div>
                    <p class="mt-1 text-[11px] text-outline">{{ __('admin.campaign_budget_limit_hint') }} <span class="font-mono font-semibold text-primary" x-text="formatVND(campaignSettings.max_budget)"></span></p>
                </div>
                <div x-show="form.sponsor_type !== 'none'">
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_description_label') }}</label>
                    <textarea x-model="form.sponsor_description" rows="2" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface"></textarea>
                </div>

            </div>

        </div>
    </div>
    <div x-show="showAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-md rounded-xl bg-surface-container-lowest border border-outline-variant p-5 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-base text-on-surface">{{ __('admin.add_manual_item_title') }}</h3>
                <button type="button" @click="showAddItemModal = false" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_category_label') }}</label>
                    <select x-model="newItem.category" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                        <template x-for="category in itemCategories" :key="category"><option :value="category" x-text="category"></option></template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.item_name_placeholder') }}</label>
                    <input type="text" x-model="newItem.name" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.price_vnd') }}</label>
                    <input type="number" min="0" x-model="newItem.price" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs font-mono text-on-surface">
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="showAddItemModal = false" class="px-4 py-2 rounded-lg bg-surface-container text-on-surface text-xs font-semibold">{{ __('admin.cancel') }}</button>
                <button type="button" @click="confirmAddItem()" class="px-4 py-2 rounded-lg bg-primary text-on-primary text-xs font-semibold">{{ __('admin.add_new_item_btn') }}</button>
            </div>
        </div>
    </div>
    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @keydown.escape.window="showConfirmModal = false">
        <div class="w-full max-w-lg rounded-xl bg-surface-container-lowest border border-outline-variant p-5 shadow-2xl" @click.outside="showConfirmModal = false">
            <div class="flex items-center justify-between border-b border-outline-variant pb-3">
                <h3 class="font-bold text-base text-on-surface">{{ __('admin.confirm_campaign_publish_title') }}</h3>
                <button type="button" @click="showConfirmModal = false" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="grid grid-cols-2 gap-3 py-4 text-xs">
                <div><span class="text-outline">{{ __('admin.campaign_name') }}</span><p class="font-semibold text-on-surface truncate" x-text="form.name"></p></div>
                <div><span class="text-outline">{{ __('admin.restaurant_brand') }}</span><p class="font-semibold text-on-surface truncate" x-text="form.restaurant"></p></div>
                <div><span class="text-outline">{{ __('admin.max_budget_ceiling') }}</span><p class="font-mono font-semibold text-primary" x-text="formatVND(form.max_budget)"></p></div>
                <div><span class="text-outline">{{ __('admin.menu_item_count_label') }}</span><p class="font-semibold text-on-surface" x-text="menuItems.length"></p></div>
                <div class="col-span-2"><span class="text-outline">{{ __('admin.order_deadline') }}</span><p class="font-semibold text-on-surface" x-text="form.deadline || '—'"></p></div>
            </div>
            <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">{{ __('admin.confirm_campaign_publish_message') }}</p>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" @click="showConfirmModal = false" class="px-4 py-2 rounded-lg border border-outline-variant text-on-surface text-xs font-semibold">{{ __('admin.cancel') }}</button>
                <button type="button" @click="confirmPublish()" :disabled="submitting" class="px-4 py-2 rounded-lg bg-primary text-on-primary text-xs font-semibold disabled:opacity-50">{{ __('admin.confirm_publish_campaign') }}</button>
            </div>
        </div>
    </div>
</div>
</x-admin.layout>
