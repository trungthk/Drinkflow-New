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
                <input name="q" value="{{ $filters['search'] ?? '' }}" class="sa-input" placeholder="{{ __('superadmin.rooms.search') }}">
                <select name="status" class="sa-input">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (['active', 'disabled', 'archived'] as $value)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ __('superadmin.common.'.$value) }}</option>
                    @endforeach
                </select>
                <select name="sort" class="sa-input">
                    <option value="" @selected(($filters['sort'] ?? '') === '')>{{ __('superadmin.rooms.sort_newest') }}</option>
                    <option value="name" @selected(($filters['sort'] ?? '') === 'name')>{{ __('superadmin.rooms.sort_name') }}</option>
                    <option value="members" @selected(($filters['sort'] ?? '') === 'members')>{{ __('superadmin.rooms.sort_members') }}</option>
                    <option value="campaigns" @selected(($filters['sort'] ?? '') === 'campaigns')>{{ __('superadmin.rooms.sort_campaigns') }}</option>
                </select>
                <button class="sa-button secondary" type="submit">{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.room') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th>{{ __('superadmin.rooms.members') }}</th>
                        <th>{{ __('superadmin.common.campaigns') }}</th>
                        <th>{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td><strong>{{ $room->name }}</strong><br><small>{{ $room->slug }}</small></td>
                            <td><x-superadmin.status-pill :status="$room->status" /></td>
                            <td>{{ $room->room_users_count }}</td>
                            <td>{{ $room->campaigns_count }}</td>
                            <td>
                                <a class="sa-button secondary" href="{{ route('superadmin.rooms.detail.page', $room) }}">{{ __('superadmin.common.details') }}</a>
                                <button class="sa-button danger" type="button" data-action="delete-room" data-room-id="{{ $room->id }}" data-room-name="{{ $room->name }}">{{ __('superadmin.common.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sa-empty">{{ __('superadmin.rooms.no_results') }}</td></tr>
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
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
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

        document.querySelector('#create-room-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const errorBox = document.querySelector('#create-room-modal-error');
            errorBox?.classList.add('hidden');
            try {
                await dfApi('{{ route('superadmin.rooms.store') }}', {
                    method: 'POST',
                    body: { name: form.name.value, slug: form.slug.value },
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
