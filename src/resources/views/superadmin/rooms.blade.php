@extends('superadmin.layout', ['title' => __('superadmin.rooms.title'), 'active' => 'rooms'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.core_system') }}</p>
            <h1>{{ __('superadmin.rooms.title') }}</h1>
        </div>
        <button class="sa-button" type="button" data-modal-open="create-room-modal">
            <span class="material-symbols-outlined text-[16px]">add_home</span>{{ __('superadmin.rooms.create') }}
        </button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.rooms.all_rooms') }}</h2>
                <p>{{ __('superadmin.common.rooms_count', ['count' => $rooms->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <x-superadmin.search-input :value="$filters['search'] ?? ''" placeholder="{{ __('superadmin.rooms.search') }}" />
                <select name="status" class="sa-input">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (['active', 'inactive', 'archived'] as $value)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ __('superadmin.common.'.$value) }}</option>
                    @endforeach
                </select>
                <select name="sort" class="sa-input">
                    <option value="" @selected(($filters['sort'] ?? '') === '')>{{ __('superadmin.rooms.sort_newest') }}</option>
                    <option value="name" @selected(($filters['sort'] ?? '') === 'name')>{{ __('superadmin.rooms.sort_name') }}</option>
                    <option value="members" @selected(($filters['sort'] ?? '') === 'members')>{{ __('superadmin.rooms.sort_members') }}</option>
                    <option value="campaigns" @selected(($filters['sort'] ?? '') === 'campaigns')>{{ __('superadmin.rooms.sort_campaigns') }}</option>
                </select>
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.room') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th class="whitespace-nowrap">{{ __('superadmin.rooms.members') }}</th>
                        <th class="whitespace-nowrap">{{ __('superadmin.common.campaigns') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="superadmin-avatar shrink-0">{{ mb_strtoupper(mb_substr($room->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <strong class="block truncate">{{ $room->name }}</strong>
                                        <small class="block truncate text-outline">{{ $room->slug }}</small>
                                    </span>
                                </div>
                            </td>
                            <td><x-superadmin.status-pill :status="$room->status" /></td>
                            <td class="whitespace-nowrap">{{ $room->room_users_count }}</td>
                            <td class="whitespace-nowrap">{{ $room->campaigns_count }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a class="sa-button secondary" href="{{ route('superadmin.rooms.detail.page', $room) }}"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.common.details') }}</a>
                                    <button class="sa-button danger" type="button" data-action="delete-room" data-room-id="{{ $room->id }}" data-room-name="{{ $room->name }}"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.common.delete') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-superadmin.empty-state icon="search_off" :title="__('superadmin.rooms.no_results_title')" :description="__('superadmin.rooms.no_results_description')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rooms->links() }}</div>
    </section>

    <x-superadmin.modal id="create-room-modal" icon="add_home" :title="__('superadmin.rooms.create')" :description="__('superadmin.rooms.create_description')">
        <form id="create-room-form" class="space-y-4">
            @csrf
            <div>
                <label for="create-room-name" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_name') }} <span class="text-error">*</span></label>
                <input type="text" id="create-room-name" name="name" required class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="create-room-slug" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_slug') }} <span class="text-error">*</span></label>
                <input type="text" id="create-room-slug" name="slug" required pattern="[a-z0-9-]+" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="create-room-status" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_status') }}</label>
                <select id="create-room-status" name="status" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    @foreach (['active', 'inactive', 'archived'] as $value)
                        <option value="{{ $value }}" @selected($value === 'active')>{{ __('superadmin.common.'.$value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_admins') }}</label>
                <div id="create-room-admin-options" class="max-h-40 overflow-y-auto space-y-1 border border-outline-variant rounded-lg p-2">
                    <p class="sa-empty">{{ __('global.common.loading') }}</p>
                </div>
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">add_home</span>{{ __('superadmin.rooms.create') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>
@endsection
@push('scripts')
    <script>
        const roomsNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };

        async function loadCreateRoomAdminOptions() {
            const container = document.querySelector('#create-room-admin-options');
            if (!container) return;
            try {
                const admins = [];
                let nextPageUrl = '{{ route('superadmin.admins.index', ['role' => 'admin']) }}';
                while (nextPageUrl) {
                    const { data: page } = await dfApi(nextPageUrl);
                    admins.push(...page.data);
                    nextPageUrl = page.next_page_url;
                }
                container.innerHTML = admins.map(admin => `
                    <label class="flex items-center gap-2 px-1 py-1 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="admin_ids[]" value="${admin.id}">
                        <span>${escapeHtml(admin.name)} <span class="text-outline">(${escapeHtml(admin.email)})</span></span>
                    </label>
                `).join('') || `<p class="sa-empty">${@js(__('superadmin.rooms.no_admin_options'))}</p>`;
            } catch (error) {
                container.innerHTML = `<p class="sa-empty">${escapeHtml(error.message)}</p>`;
            }
        }
        loadCreateRoomAdminOptions();

        document.querySelector('#create-room-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const errorBox = document.querySelector('#create-room-modal-error');
            errorBox?.classList.add('hidden');
            const adminIds = [...form.querySelectorAll('input[name="admin_ids[]"]:checked')].map(input => Number(input.value));
            try {
                await dfApi('{{ route('superadmin.rooms.store') }}', {
                    method: 'POST',
                    body: { name: form.name.value, slug: form.slug.value, status: form.status.value, admin_ids: adminIds },
                });
                window.location.reload();
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                }
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = false;
                    button.classList.remove('opacity-75', 'cursor-wait');
                    if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
                }
            }
        });

        document.addEventListener('click', (event) => {
            const deleteBtn = event.target.closest('[data-action="delete-room"]');
            if (!deleteBtn) return;
            openSuperadminConfirm({
                message: @js(__('superadmin.rooms.confirm_delete')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/rooms/${deleteBtn.dataset.roomId}`, { method: 'DELETE' });
                    roomsNotice(@js(__('superadmin.rooms.deleted')));
                    window.setTimeout(() => window.location.reload(), 500);
                },
            });
        });
    </script>
@endpush
