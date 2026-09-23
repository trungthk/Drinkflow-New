{{-- Modal hiển thị toàn bộ đơn hàng của một chiến dịch trong phòng (dùng chung bởi trang Công nợ và trang Chiến dịch). --}}
{{-- Yêu cầu component cha có sẵn Alpine state: campaignModalOpen, campaignLoading, campaignData, openCampaignDetail(campaignId). --}}
<template x-teleport="body">
    <div x-show="campaignModalOpen" x-cloak @keydown.escape.window="campaignModalOpen = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-md transition-opacity duration-200"
        role="dialog" aria-modal="true">
        <div class="bg-surface-container-lowest w-full max-w-2xl max-h-[90vh] rounded-2xl shadow-2xl border border-outline-variant overflow-hidden flex flex-col"
            @click.outside="campaignModalOpen = false">
            <!-- Modal Header -->
            <div
                class="p-3.5 sm:p-4 border-b border-outline-variant/30 flex items-start justify-between bg-surface-container-low gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div
                        class="w-10 h-10 rounded-xl bg-primary-fixed flex items-center justify-center text-on-primary-fixed-variant shrink-0 mt-0.5">
                        <span class="material-symbols-outlined text-[22px]">campaign</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm sm:text-base text-on-surface font-bold truncate"
                                x-text="campaignData?.name || '{{ __('room.debts.campaign_details_title') }}'"></h3>
                            <template x-if="campaignData">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="campaignData.is_full_sponsor ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-surface-container-high text-on-surface-variant'">
                                    <span class="material-symbols-outlined text-[13px]"
                                        x-text="campaignData.is_full_sponsor ? 'verified' : 'account_balance_wallet'"></span>
                                    <span
                                        x-text="campaignData.is_full_sponsor ? '{{ __('room.debts.sponsor_badge_full') }}' : '{{ __('room.debts.sponsor_badge_none') }}'"></span>
                                </span>
                            </template>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-on-surface-variant mt-1">
                            <template x-if="campaignData?.restaurant">
                                <span class="flex items-center gap-1">
                                    <span
                                        class="material-symbols-outlined text-[14px] text-primary">storefront</span>
                                    <span class="font-medium text-on-surface"
                                        x-text="campaignData.restaurant"></span>
                                </span>
                            </template>
                            <template x-if="campaignData?.code">
                                <span class="flex items-center gap-1 font-mono text-[11px]">
                                    <span class="material-symbols-outlined text-[13px]">tag</span>
                                    <span x-text="campaignData.code"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
                <button
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer shrink-0"
                    @click="campaignModalOpen = false">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- Notice Banner -->
            <template x-if="campaignData">
                <div class="px-4 py-2.5 text-xs flex items-start gap-2 border-b"
                    :class="campaignData.is_full_sponsor ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-sky-50 text-sky-900 border-sky-200'">
                    <span class="material-symbols-outlined text-[17px] shrink-0 mt-0.5"
                        :class="campaignData.is_full_sponsor ? 'text-emerald-700' : 'text-sky-700'"
                        x-text="campaignData.is_full_sponsor ? 'redeem' : 'info'"></span>
                    <span class="leading-relaxed"
                        x-text="campaignData.is_full_sponsor ? '{{ __('room.debts.campaign_full_sponsor_notice') }}' : '{{ __('room.debts.campaign_my_orders_notice') }}'"></span>
                </div>
            </template>

            <!-- Modal Body (Orders List) -->
            <div class="p-3.5 sm:p-4 overflow-y-auto max-h-[60vh] space-y-3.5 bg-surface-container-lowest">
                <!-- Loading skeleton -->
                <template x-if="campaignLoading">
                    <div class="space-y-3 py-4">
                        <div class="animate-pulse flex space-x-3 items-center">
                            <div class="rounded-full bg-slate-200 h-9 w-9"></div>
                            <div class="flex-1 space-y-1.5 py-1">
                                <div class="h-3.5 bg-slate-200 rounded w-1/3"></div>
                                <div class="h-2.5 bg-slate-200 rounded w-1/4"></div>
                            </div>
                        </div>
                        <div class="animate-pulse space-y-2 pt-2">
                            <div class="h-14 bg-slate-100 rounded-xl"></div>
                            <div class="h-14 bg-slate-100 rounded-xl"></div>
                        </div>
                    </div>
                </template>

                <!-- Empty orders state -->
                <template
                    x-if="!campaignLoading && campaignData && (!campaignData.orders || campaignData.orders.length === 0)">
                    <div
                        class="py-10 text-center text-on-surface-variant flex flex-col items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[36px] text-outline-variant">receipt_long</span>
                        <span class="text-xs font-medium">{{ __('room.debts.campaign_empty_orders') }}</span>
                    </div>
                </template>

                <!-- Orders list -->
                <template
                    x-if="!campaignLoading && campaignData && campaignData.orders && campaignData.orders.length > 0">
                    <div class="space-y-3">
                        <template x-for="(order, idx) in campaignData.orders" :key="order.id">
                            <div
                                class="border border-outline-variant/40 rounded-xl bg-surface-container-low/40 overflow-hidden shadow-2xs">
                                <!-- Order Header -->
                                <div
                                    class="px-3 py-2 bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <template x-if="order.orderer_avatar">
                                            <img :src="order.orderer_avatar" :alt="order.orderer_name"
                                                loading="lazy"
                                                class="w-7 h-7 rounded-full object-cover shrink-0 border border-outline-variant/50" />
                                        </template>
                                        <template x-if="!order.orderer_avatar">
                                            <div
                                                class="w-7 h-7 rounded-full bg-primary-fixed text-on-primary-fixed-variant flex items-center justify-center text-[12px] font-bold shrink-0">
                                                <span
                                                    x-text="(order.orderer_name || 'U').charAt(0).toUpperCase()"></span>
                                            </div>
                                        </template>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-on-surface truncate"
                                                    x-text="order.orderer_name"></span>
                                                <template x-if="order.orderer_code">
                                                    <span
                                                        class="text-[10px] font-mono font-medium px-1.5 py-0.2 rounded bg-surface-container-high text-on-surface-variant"
                                                        x-text="order.orderer_code"></span>
                                                </template>
                                            </div>
                                            <span class="text-[10px] font-mono text-on-surface-variant"
                                                x-text="'#' + order.code"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-surface-container-high text-on-surface-variant">
                                            <span x-text="order.status_label || order.status"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Items List -->
                                <div class="p-3 divide-y divide-outline-variant/20 space-y-2">
                                    <template x-for="item in order.items" :key="item.id">
                                        <div class="pt-2 first:pt-0 flex items-start gap-2.5 text-xs">
                                            <div
                                                class="relative w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-primary shrink-0 overflow-hidden">
                                                <span class="material-symbols-outlined text-[16px]">emoji_food_beverage</span>
                                                <template x-if="item.image_url">
                                                    <img :src="item.image_url" :alt="item.item_name" loading="lazy"
                                                        class="absolute inset-0 h-full w-full object-cover"
                                                        onerror="this.remove()">
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1 flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="font-semibold text-on-surface"
                                                        x-text="item.item_name"></span>
                                                    <template x-if="item.size_name">
                                                        <span
                                                            class="text-[10px] px-1.5 py-0.2 rounded bg-primary-fixed/30 text-primary font-medium"
                                                            x-text="item.size_name"></span>
                                                    </template>
                                                    <span class="text-on-surface-variant font-medium"
                                                        x-text="'x' + item.quantity"></span>
                                                </div>

                                                <!-- Options & Note -->
                                                <div
                                                    class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-on-surface-variant">
                                                    <template
                                                        x-if="item.ice_percent !== null && item.ice_percent !== undefined">
                                                        <span>{{ __('room.debts.item_ice') }}: <strong
                                                                x-text="item.ice_percent + '%'"></strong></span>
                                                    </template>
                                                    <template
                                                        x-if="item.sugar_percent !== null && item.sugar_percent !== undefined">
                                                        <span>{{ __('room.debts.item_sugar') }}: <strong
                                                                x-text="item.sugar_percent + '%'"></strong></span>
                                                    </template>
                                                    <template x-if="item.note">
                                                        <span class="italic text-amber-800 bg-amber-50 px-1 rounded"
                                                            x-text="item.note"></span>
                                                    </template>
                                                </div>

                                                <!-- Toppings -->
                                                <template x-if="item.toppings && item.toppings.length > 0">
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        <template x-for="top in item.toppings" :key="top.name">
                                                            <span
                                                                class="inline-flex items-center text-[10px] bg-surface-container-high px-1.5 py-0.5 rounded text-on-surface-variant">
                                                                <span x-text="'+ ' + top.name"></span>
                                                                <template x-if="top.price > 0">
                                                                    <span
                                                                        class="ml-1 font-mono text-primary font-medium"
                                                                        x-text="'(' + new Intl.NumberFormat('vi-VN').format(top.price) + 'đ)'"></span>
                                                                </template>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="text-right shrink-0 font-tabular-nums">
                                                <div class="font-bold text-on-surface text-xs"
                                                    x-text="new Intl.NumberFormat('vi-VN').format(item.line_subtotal) + 'đ'">
                                                </div>
                                                <div class="text-[10px] text-on-surface-variant"
                                                    x-text="'{{ __('room.debts.unit_price_per_item', ['amount' => ':amount']) }}'.replace(':amount', new Intl.NumberFormat('vi-VN').format(item.unit_price) + 'đ')">
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Order Subtotal Footer -->
                                <div
                                    class="px-3 py-2 bg-surface-container-low/60 border-t border-outline-variant/30 flex items-center justify-between text-xs font-medium">
                                    <span
                                        class="text-on-surface-variant">{{ __('room.debts.campaign_total_amount') }}</span>
                                    <div class="flex items-baseline gap-2">
                                        <template x-if="order.sponsor_amount > 0">
                                            <span class="text-[10px] text-secondary font-medium"
                                                x-text="'{{ __('room.debts.sponsored_amount', ['amount' => ':amount']) }}'.replace(':amount', new Intl.NumberFormat('vi-VN').format(order.sponsor_amount) + 'đ')"></span>
                                        </template>
                                        <span class="font-bold font-tabular-nums text-xs text-primary"
                                            x-text="new Intl.NumberFormat('vi-VN').format(order.final_amount) + 'đ'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div
                class="p-3 sm:p-3.5 border-t border-outline-variant/30 flex items-center justify-between bg-surface-container-low text-xs">
                <div class="flex items-center gap-3 text-on-surface-variant">
                    <template x-if="campaignData">
                        <span class="flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-[15px] text-primary">shopping_bag</span>
                            <span
                                x-text="campaignData.total_orders + ' {{ __('room.debts.campaign_orders_count') }}' + (campaignData.total_cups ? ' (' + '{{ __('room.debts.items_count', ['count' => ':count']) }}'.replace(':count', campaignData.total_cups) + ')' : '')"></span>
                        </span>
                    </template>
                </div>
                <button type="button" @click="campaignModalOpen = false"
                    class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest text-on-surface font-semibold text-xs hover:bg-surface-container-high transition-colors cursor-pointer">
                    {{ __('global.common.close') }}
                </button>
            </div>
        </div>
    </div>
</template>
