@extends('superadmin.layout', ['title' => __('superadmin.versions.title'), 'active' => 'versions'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.release_operations') }}</p>
            <h1>{{ __('superadmin.versions.title') }}</h1>
            <p>{{ __('superadmin.versions.description') }}</p>
        </div>
        <button class="sa-button" type="button" data-action="create-release"><span class="material-symbols-outlined">add</span>{{ __('superadmin.versions.new_release') }}</button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.versions.history') }}</h2>
                <p>{{ __('superadmin.common.releases_count', ['count' => $versions->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <x-superadmin.search-input :value="$filters['search'] ?? ''" placeholder="{{ __('superadmin.versions.search') }}" />
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.versions.version') }}</th>
                        <th>{{ __('superadmin.versions.release_title') }}</th>
                        <th>{{ __('superadmin.versions.release_date') }}</th>
                        <th>{{ __('superadmin.versions.flags') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($versions as $version)
                        <tr>
                            <td class="whitespace-nowrap"><strong>{{ $version->version }}</strong></td>
                            <td>{{ $version->title }}</td>
                            <td class="whitespace-nowrap">{{ $version->release_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($version->important)
                                        <span class="status-pill status-pending"><span class="material-symbols-outlined text-[14px]">priority_high</span>{{ __('superadmin.versions.important') }}</span>
                                    @endif
                                    @if($version->force_refresh)
                                        <span class="status-pill status-scheduled"><span class="material-symbols-outlined text-[14px]">refresh</span>{{ __('superadmin.versions.force_refresh') }}</span>
                                    @endif
                                    @if(! $version->important && ! $version->force_refresh)
                                        <span class="text-outline">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <button class="sa-button secondary" type="button" data-action="edit-release"
                                        data-update-url="{{ route('superadmin.versions.update', $version) }}"
                                        data-release="{{ json_encode([
                                            'version' => $version->version,
                                            'title' => $version->title,
                                            'changelog' => (string) $version->changelog,
                                            'release_date' => $version->release_date?->format('Y-m-d'),
                                            'important' => (bool) $version->important,
                                            'force_refresh' => (bool) $version->force_refresh,
                                        ], JSON_UNESCAPED_UNICODE) }}"><span class="material-symbols-outlined text-[16px]">edit</span>{{ __('superadmin.common.edit') }}</button>
                                    <button class="sa-button danger" type="button" data-action="delete-release"
                                        data-delete-url="{{ route('superadmin.versions.destroy', $version) }}"
                                        data-version="{{ $version->version }}"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.common.delete') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                @if(($filters['search'] ?? '') !== '')
                                    <x-superadmin.empty-state icon="search_off" :title="__('superadmin.versions.no_results_title')" :description="__('superadmin.versions.no_results_description')" />
                                @else
                                    <x-superadmin.empty-state icon="new_releases" :title="__('superadmin.versions.no_releases_title')" :description="__('superadmin.versions.no_releases_description')" />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $versions->links() }}</div>
    </section>

    <x-superadmin.modal id="version-modal" icon="new_releases" :title="__('superadmin.versions.new_release')" :description="__('superadmin.versions.create_description')" max-width="max-w-3xl">
        <form id="version-form" class="space-y-4" data-no-loading
            data-store-url="{{ route('superadmin.versions.store') }}"
            data-preview-url="{{ route('superadmin.versions.preview') }}"
            data-max-length="{{ \App\Http\Requests\VersionRequest::CHANGELOG_MAX_LENGTH }}"
            data-max-upload-kb="100"
            data-i18n="{{ json_encode(__('superadmin.versions'), JSON_UNESCAPED_UNICODE) }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="version-field-version" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.versions.field_version') }} <span class="text-error">*</span></label>
                    <input type="text" id="version-field-version" name="version" required maxlength="50" autocomplete="off" placeholder="{{ __('superadmin.versions.field_version_placeholder') }}" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="version-field-release-date" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.versions.field_release_date') }}</label>
                    <input type="date" id="version-field-release-date" name="release_date" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>
            </div>
            <div>
                <label for="version-field-title" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.versions.field_title') }} <span class="text-error">*</span></label>
                <input type="text" id="version-field-title" name="title" required maxlength="255" autocomplete="off" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex items-start gap-2.5 rounded-lg border border-outline-variant p-3 cursor-pointer hover:bg-surface-container-low">
                    <input type="checkbox" name="important" class="mt-0.5 accent-primary">
                    <span><span class="block text-xs font-semibold text-on-surface">{{ __('superadmin.versions.important') }}</span><span class="block text-[11px] text-outline mt-0.5">{{ __('superadmin.versions.important_hint') }}</span></span>
                </label>
                <label class="flex items-start gap-2.5 rounded-lg border border-outline-variant p-3 cursor-pointer hover:bg-surface-container-low">
                    <input type="checkbox" name="force_refresh" class="mt-0.5 accent-primary">
                    <span><span class="block text-xs font-semibold text-on-surface">{{ __('superadmin.versions.force_refresh') }}</span><span class="block text-[11px] text-outline mt-0.5">{{ __('superadmin.versions.force_refresh_hint') }}</span></span>
                </label>
            </div>

            <div>
                <div class="flex flex-wrap items-end justify-between gap-2 mb-1.5">
                    <label for="version-field-changelog" class="block text-xs font-semibold text-on-surface">{{ __('superadmin.versions.field_changelog') }}</label>
                    <div class="flex items-center gap-2">
                        <span class="sa-md-badge"><span class="material-symbols-outlined text-[14px]">markdown</span>{{ __('superadmin.versions.markdown_supported') }}</span>
                        <label class="sa-button secondary !py-1.5 cursor-pointer" title="{{ __('superadmin.versions.upload_hint') }}">
                            <span class="material-symbols-outlined text-[16px]">upload_file</span>{{ __('superadmin.versions.upload_md') }}
                            <input type="file" id="version-md-upload" class="sr-only" accept=".md,.markdown,.txt,text/markdown,text/plain">
                        </label>
                    </div>
                </div>
                <div class="sa-md-editor">
                    <div class="sa-md-toolbar">
                        <div class="sa-md-tabs" role="tablist">
                            <button type="button" role="tab" class="is-active" data-md-tab="write" aria-selected="true"><span class="material-symbols-outlined text-[16px]">edit_note</span>{{ __('superadmin.versions.tab_write') }}</button>
                            <button type="button" role="tab" data-md-tab="preview" aria-selected="false"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.versions.tab_preview') }}</button>
                        </div>
                        <div class="sa-md-actions" data-md-actions>
                            @foreach([
                                'bold' => 'format_bold', 'italic' => 'format_italic', 'heading' => 'title',
                                'list' => 'format_list_bulleted', 'ordered_list' => 'format_list_numbered',
                                'quote' => 'format_quote', 'code' => 'code', 'link' => 'link',
                            ] as $format => $icon)
                                <button type="button" data-md-format="{{ $format }}" title="{{ __('superadmin.versions.md_'.$format) }}" aria-label="{{ __('superadmin.versions.md_'.$format) }}"><span class="material-symbols-outlined text-[18px]">{{ $icon }}</span></button>
                            @endforeach
                        </div>
                    </div>
                    <textarea id="version-field-changelog" name="changelog" rows="14" spellcheck="false"
                        placeholder="{{ __('superadmin.versions.markdown_placeholder') }}" class="sa-md-textarea"></textarea>
                    <div id="version-changelog-preview" class="sa-md-preview sa-markdown hidden" aria-live="polite"></div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 mt-1.5 text-[11px] text-outline">
                    <span>{{ __('superadmin.versions.upload_hint') }}</span>
                    <span id="version-changelog-count"></span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">save</span><span data-submit-label>{{ __('superadmin.versions.save_release') }}</span>
                </button>
            </div>
        </form>
    </x-superadmin.modal>

    {{-- Preview placeholders cloned by resources/js/superadmin/versions.js so their strings stay translated through __(). --}}
    <template id="tpl-preview-empty"><x-superadmin.empty-state icon="preview" :bordered="false" class="!py-8" :description="__('superadmin.versions.preview_empty')" /></template>
    <template id="tpl-preview-loading"><x-superadmin.empty-state loading :bordered="false" class="!py-8" :description="__('superadmin.versions.preview_loading')" /></template>
    <template id="tpl-preview-failed"><x-superadmin.empty-state icon="error" :bordered="false" class="!py-8" :title="__('superadmin.versions.preview_failed')" description="" /></template>
@endsection
