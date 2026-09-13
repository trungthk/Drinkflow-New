<x-admin.layout :title="__('admin.fast_create_campaign')" active="campaigns" :room="$room">
<div class="max-w-6xl mx-auto space-y-6" x-data="campaignCreateComponent()">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-outline-variant">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}" class="w-9 h-9 rounded-lg border border-outline-variant flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold text-on-surface tracking-tight flex items-center gap-2">
                    {{ __('admin.fast_create_campaign') }}
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-primary/10 text-primary uppercase border border-primary/20">Fast Dispatcher</span>
                </h1>
                <p class="text-xs text-outline">{{ __('admin.campaign_create_subtitle', ['room' => $room->name]) ?? 'Khởi tạo đợt đặt món mới với nguồn thực đơn linh hoạt và chia sẻ chi phí tự động' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <button type="button" @click="saveDraft()" :disabled="submitting" class="px-4 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1.5 disabled:opacity-50">
                <span class="material-symbols-outlined text-[16px]">save</span>
                <span>{{ __('admin.save_draft') ?? 'Lưu bản nháp' }}</span>
            </button>
            <button type="button" @click="publishCampaign()" :disabled="submitting" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50">
                <span class="material-symbols-outlined text-[16px]">rocket_launch</span>
                <span>{{ __('admin.publish_campaign') }}</span>
            </button>
        </div>
    </div>

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
                <div class="text-[10px] text-outline truncate">Cà phê, Phin Freeze, Trà</div>
            </button>
            <button type="button" @click="applyPreset('phuclong')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Phúc Long Tea</div>
                <div class="text-[10px] text-outline truncate">Trà Ô Long, Trà Đào sữa</div>
            </button>
            <button type="button" @click="applyPreset('gongcha')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Gong Cha</div>
                <div class="text-[10px] text-outline truncate">Trà sữa trân châu hoàng kim</div>
            </button>
            <button type="button" @click="applyPreset('tocotoco')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">TocoToco Tea</div>
                <div class="text-[10px] text-outline truncate">Trà sữa Panda, Ba Anh Em</div>
            </button>
            <button type="button" @click="applyPreset('starbucks')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Starbucks</div>
                <div class="text-[10px] text-outline truncate">Frappuccino, Cold Brew</div>
            </button>
            <button type="button" @click="applyPreset('comtam')" class="p-2.5 rounded-lg border border-outline-variant/80 hover:border-primary hover:bg-primary/5 transition-all text-left group">
                <div class="font-bold text-xs text-on-surface group-hover:text-primary truncate">Cơm Tấm Phúc Lộc</div>
                <div class="text-[10px] text-outline truncate">Cơm sườn bì chả, trứng ốp</div>
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
                        <input type="text" x-model="form.name" placeholder="Ví dụ: Trà Chiều Thứ Sáu ☕" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.restaurant_brand') }} <span class="text-error">*</span></label>
                        <input type="text" x-model="form.restaurant" placeholder="Ví dụ: Highlands Coffee - Chi nhánh Bitexco" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
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
                        <button type="button" @click="menuTab = 'manual'" :class="menuTab === 'manual' ? 'bg-surface-container-lowest font-bold text-primary shadow-sm' : 'text-outline hover:text-on-surface'" class="px-2.5 py-1 rounded transition-all">
                            {{ __('admin.source_saved') }}
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
                <div x-show="menuTab === 'reuse'" class="space-y-3">
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

                <!-- Tab 2: Manual Builder -->
                <div x-show="menuTab === 'manual'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline">{{ __('admin.menu_items_count', ['count' => '']) }}<span x-text="menuItems.length"></span>:</span>
                        <button type="button" @click="addMenuItem()" class="px-3 py-1 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/30 rounded text-xs font-semibold transition-colors flex items-center gap-1">
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
                            </div>
                        </template>

                        <div x-show="menuItems.length === 0" class="text-center py-6 text-xs text-outline italic">
                            {{ __('admin.no_items_yet') }}
                        </div>
                    </div>
                </div>

                <!-- Tab 3: URL Crawler -->
                <div x-show="menuTab === 'crawler'" class="space-y-3">
                    <div class="text-xs text-outline">{{ __('admin.crawler_desc') }}</div>
                    <div class="flex gap-2">
                        <input type="url" x-model="crawlerUrl" placeholder="https://shopeefood.vn/ho-chi-minh/highlands-coffee-..." class="flex-1 px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                        <button type="button" @click="previewCrawler()" :disabled="crawlerLoading || !crawlerUrl" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs font-semibold hover:bg-primary-container disabled:opacity-50 transition-colors flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]" :class="crawlerLoading ? 'animate-spin' : ''">sync</span>
                            <span>{{ __('admin.crawl_menu_btn') }}</span>
                        </button>
                    </div>
                    <div x-show="crawlerMessage" class="text-xs p-2.5 rounded bg-surface-container-low border border-outline-variant text-on-surface" x-text="crawlerMessage"></div>
                </div>

                <!-- Tab 4: JSON Schema -->
                <div x-show="menuTab === 'json'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline">{{ __('admin.json_import_desc') }}</span>
                        <button type="button" @click="loadSampleJson()" class="text-xs text-primary hover:underline font-medium">{{ __('admin.view_sample_json') }}</button>
                    </div>
                    <textarea x-model="rawJson" rows="6" placeholder='[{"name": "Trà Sen Vàng", "price": 45000, "category": "Trà"}, {"name": "Freeze Trà Xanh", "price": 55000, "category": "Freeze"}]' class="w-full font-mono text-xs p-3 bg-surface border border-outline-variant rounded-lg text-on-surface focus:outline-none focus:border-primary"></textarea>
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
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_name_label') }}</label>
                    <input type="text" x-model="form.sponsor_name" placeholder="Ví dụ: Team Leader, Quỹ Phòng, Sếp Dũng..." class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('admin.sponsor_max_budget_label') }}</label>
                    <div class="relative">
                        <input type="number" x-model="form.max_budget" placeholder="{{ __('admin.unlimited_budget_placeholder') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-8">
                        <span class="absolute right-3 top-2 text-xs text-outline font-mono">đ</span>
                    </div>
                </div>

                <!-- Fee Adjustments -->
                <div class="border-t border-outline-variant/60 pt-3 space-y-3">
                    <span class="text-xs font-bold text-on-surface uppercase tracking-wider block">{{ __('admin.delivery_and_discounts') }}</span>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] text-outline mb-1">{{ __('admin.delivery_fee_label') }}</label>
                            <div class="relative">
                                <input type="number" x-model="form.delivery_fee" placeholder="0" class="w-full px-2.5 py-1.5 bg-surface border border-outline-variant rounded text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-6">
                                <span class="absolute right-2 top-1.5 text-[10px] text-outline font-mono">đ</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] text-outline mb-1">{{ __('admin.voucher_discount_label') }}</label>
                            <div class="relative">
                                <input type="number" x-model="form.discount" placeholder="0" class="w-full px-2.5 py-1.5 bg-surface border border-outline-variant rounded text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-6">
                                <span class="absolute right-2 top-1.5 text-[10px] text-outline font-mono">đ</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] text-outline mb-1">{{ __('admin.flat_price_label') }}</label>
                        <div class="relative">
                            <input type="number" x-model="form.flat_price" placeholder="{{ __('admin.flat_price_placeholder') }}" class="w-full px-2.5 py-1.5 bg-surface border border-outline-variant rounded text-xs font-mono text-on-surface focus:outline-none focus:border-primary pr-6">
                            <span class="absolute right-2 top-1.5 text-[10px] text-outline font-mono">đ</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Summary & Publish Box -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-2 border-b border-outline-variant/60 pb-2.5">
                    <span class="material-symbols-outlined text-[18px] text-primary">analytics</span>
                    {{ __('admin.creation_summary') }}
                </h2>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-outline">
                        <span>{{ __('admin.menu_item_count_label') }}</span>
                        <span class="font-bold text-on-surface font-mono" x-text="menuItems.length"></span>
                    </div>
                    <div class="flex justify-between text-outline">
                        <span>{{ __('admin.shared_delivery_fee') }}</span>
                        <span class="font-mono text-on-surface" x-text="formatVND(form.delivery_fee || 0)"></span>
                    </div>
                    <div class="flex justify-between text-outline">
                        <span>{{ __('admin.voucher_deduction') }}</span>
                        <span class="font-mono text-error" x-text="'-' + formatVND(form.discount || 0)"></span>
                    </div>
                    <div class="flex justify-between text-outline" x-show="form.sponsor_name">
                        <span>{{ __('admin.sponsor_label') }}</span>
                        <span class="font-bold text-primary truncate max-w-[120px]" x-text="form.sponsor_name"></span>
                    </div>
                </div>

                <div class="border-t border-outline-variant pt-3 space-y-2">
                    <button type="button" @click="publishCampaign()" :disabled="submitting" class="w-full py-2.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                        <span class="material-symbols-outlined text-[18px]">campaign</span>
                        <span>{{ __('admin.publish_and_open_now') }}</span>
                    </button>
                    <button type="button" @click="saveDraft()" :disabled="submitting" class="w-full py-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-on-surface text-xs font-semibold transition-all">
                        {{ __('admin.save_as_draft') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</x-admin.layout>
