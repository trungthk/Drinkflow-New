{{-- URL crawler tab of the campaign create/edit form; state lives in campaignCreateComponent (crawlerUrl, crawlerReport...). --}}
<div class="space-y-3">
    <div class="text-xs text-outline">{{ __('admin.crawler_desc') }}</div>
    <div class="flex gap-2">
        <input type="url" x-model="crawlerUrl" placeholder="{{ __('admin.crawler_url_placeholder') }}" class="flex-1 px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
        <button type="button" @click="previewCrawler()" :disabled="crawlerLoading || !crawlerUrl" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs font-semibold hover:bg-primary-container disabled:opacity-50 transition-colors flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]" :class="crawlerLoading ? 'animate-spin' : ''">sync</span>
            <span>{{ __('admin.crawl_menu_btn') }}</span>
        </button>
    </div>
    <div x-show="crawlerMessage" class="text-xs p-2.5 rounded bg-surface-container-low border border-outline-variant text-on-surface" x-text="crawlerMessage"></div>
    <div x-show="crawlerReport" x-cloak data-crawler-report class="space-y-2 rounded-lg border border-outline-variant bg-surface-container-lowest p-3 text-[11px] text-on-surface-variant">
        <p class="font-medium text-on-surface" x-text="crawlerReport?.summary"></p>
        <p x-show="crawlerReport?.skipped" x-text="crawlerReport?.skipped"></p>
        <template x-if="crawlerReport?.errors?.length">
            <div>
                <p class="mb-1 font-semibold text-error">{{ __('admin.campaign_form_crawler_report_errors') }}</p>
                <ul class="max-h-32 space-y-0.5 overflow-y-auto">
                    <template x-for="error in crawlerReport.errors" :key="error.url + error.reason">
                        <li class="break-all"><span class="font-mono" x-text="error.url"></span> — <span x-text="error.reason"></span></li>
                    </template>
                </ul>
            </div>
        </template>
    </div>
</div>
