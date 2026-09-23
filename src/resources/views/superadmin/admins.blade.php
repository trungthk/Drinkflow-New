@extends('superadmin.layout', ['title' => __('superadmin.admins.title'), 'active' => 'admins'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.core_system') }}</p>
            <h1>{{ __('superadmin.admins.title') }}</h1>
            <p>{{ __('superadmin.admins.description') }}</p>
        </div>
        <button class="sa-button" type="button" data-modal-open="create-admin-modal">
            <span class="material-symbols-outlined text-[16px]">person_add</span>{{ __('superadmin.admins.create') }}
        </button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.admins.administrators') }}</h2>
                <p>{{ __('superadmin.common.accounts_count', ['count' => $admins->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <input name="q" value="{{ $filters['search'] ?? '' }}" class="sa-input" placeholder="{{ __('superadmin.admins.search') }}">
                <select name="role" class="sa-input">
                    <option value="">{{ __('superadmin.admins.all_roles') }}</option>
                    @foreach (['admin', 'superadmin'] as $value)
                        <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ __('superadmin.admins.role_'.$value) }}</option>
                    @endforeach
                </select>
                <select name="status" class="sa-input">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (['active', 'blocked'] as $value)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ __('superadmin.common.'.$value) }}</option>
                    @endforeach
                </select>
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.admin') }}</th>
                        <th>{{ __('superadmin.common.role') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th>{{ __('superadmin.admins.assigned_rooms') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        @php
                            $isSuperadmin = $admin->isSuperadmin();
                            $statusValue = $admin->status instanceof \BackedEnum ? $admin->status->value : (string) $admin->status;
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="superadmin-avatar shrink-0">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <strong class="block truncate">{{ $admin->name }}</strong>
                                        <small class="block truncate text-outline">{{ $admin->email }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1 whitespace-nowrap font-semibold">
                                    <span class="material-symbols-outlined text-[16px]">{{ $isSuperadmin ? 'shield_person' : 'person' }}</span>{{ __('superadmin.admins.role_'.($isSuperadmin ? 'superadmin' : 'admin')) }}
                                </span>
                            </td>
                            <td><x-superadmin.status-pill :status="$statusValue" /></td>
                            <td class="whitespace-nowrap">{{ __('superadmin.common.rooms_count', ['count' => $admin->rooms_count]) }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a class="sa-button secondary" href="{{ route('superadmin.admins.detail.page', $admin) }}"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.common.details') }}</a>
                                    @if($isSuperadmin)
                                        <span class="status-pill status-active"><span class="material-symbols-outlined text-[14px]">verified_user</span>{{ __('superadmin.common.protected') }}</span>
                                    @elseif($statusValue === 'active')
                                        <button class="sa-button warning" type="button" data-action="block-admin" data-admin-id="{{ $admin->id }}" data-admin-name="{{ $admin->name }}"><span class="material-symbols-outlined text-[16px]">lock</span>{{ __('superadmin.common.block') }}</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-superadmin.empty-state icon="manage_search" :title="__('superadmin.admins.no_results_title')" :description="__('superadmin.admins.no_results_description')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $admins->links() }}</div>
    </section>

    <x-superadmin.modal id="create-admin-modal" icon="person_add" :title="__('superadmin.admins.create')" :description="__('superadmin.admins.create_description')">
        <form id="create-admin-form" class="space-y-4">
            @csrf
            <div>
                <label for="create-admin-name" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_name') }} <span class="text-error">*</span></label>
                <input type="text" id="create-admin-name" name="name" required maxlength="255" autocomplete="off" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="create-admin-email" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_email') }} <span class="text-error">*</span></label>
                <input type="email" id="create-admin-email" name="email" required maxlength="255" autocomplete="off" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="create-admin-password" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_password') }} <span class="text-error">*</span></label>
                    <x-superadmin.password-input id="create-admin-password" name="password" required minlength="8" />
                </div>
                <div>
                    <label for="create-admin-password-confirmation" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_password_confirmation') }} <span class="text-error">*</span></label>
                    <x-superadmin.password-input id="create-admin-password-confirmation" name="password_confirmation" required minlength="8" />
                </div>
                <p class="sm:col-span-2 -mt-2 text-[11px] text-outline">{{ __('superadmin.admins.password_hint') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_rooms') }}</label>
                <div id="create-admin-room-options" class="max-h-40 overflow-y-auto space-y-1 border border-outline-variant rounded-lg p-2">
                    <x-superadmin.empty-state loading :bordered="false" class="!py-6" :title="__('superadmin.common.loading_title')" :description="__('superadmin.admins.loading_rooms')" />
                </div>
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">person_add</span>{{ __('superadmin.admins.create') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>

    {{-- Empty state cloned by the page script, so its strings stay translated through __(). --}}
    <template id="tpl-no-rooms"><x-superadmin.empty-state icon="domain_disabled" :bordered="false" class="!py-6" :title="__('superadmin.admins.no_rooms_title')" :description="__('superadmin.admins.no_rooms_description')" /></template>
    <template id="tpl-load-failed"><x-superadmin.empty-state icon="error" :bordered="false" class="!py-6" :title="__('superadmin.common.load_failed_title')" description="" /></template>
@endsection
@push('scripts')
    <script>
        const adminNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };

        // The shared submit-loading handler (resources/js/superadmin/loading.js) swaps the button for a
        // spinner; restore it whenever the modal stays open (validation error).
        const resetSubmitButton = (form) => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
            if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
        };

        async function loadCreateAdminRoomOptions() {
            const container = document.querySelector('#create-admin-room-options');
            if (!container) return;
            try {
                const rooms = [];
                let nextPageUrl = @json(route('superadmin.rooms.index'));
                while (nextPageUrl) {
                    const { data: page } = await dfApi(nextPageUrl);
                    rooms.push(...page.data);
                    nextPageUrl = page.next_page_url;
                }
                if (rooms.length === 0) {
                    container.innerHTML = document.getElementById('tpl-no-rooms').innerHTML;
                    return;
                }
                container.innerHTML = rooms.map(room => `
                    <label class="flex items-center gap-2 px-1 py-1 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="room_ids[]" value="${room.id}">
                        <span>${escapeHtml(room.name)} <span class="text-outline">(${escapeHtml(room.slug)})</span></span>
                    </label>
                `).join('');
            } catch (error) {
                container.innerHTML = document.getElementById('tpl-load-failed').innerHTML;
                container.querySelector('[data-empty-description]').textContent = error.message;
            }
        }
        loadCreateAdminRoomOptions();

        // Let the browser block a mismatched confirmation before submit (and before the loading spinner).
        const createAdminForm = document.querySelector('#create-admin-form');
        const checkPasswordConfirmation = () => createAdminForm.password_confirmation.setCustomValidity(
            createAdminForm.password.value === createAdminForm.password_confirmation.value ? '' : @js(__('superadmin.admins.password_mismatch')));
        createAdminForm.password.addEventListener('input', checkPasswordConfirmation);
        createAdminForm.password_confirmation.addEventListener('input', checkPasswordConfirmation);

        createAdminForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const errorBox = document.querySelector('#create-admin-modal-error');
            errorBox?.classList.add('hidden');
            const showError = (message) => {
                errorBox.textContent = message;
                errorBox.classList.remove('hidden');
                resetSubmitButton(form);
            };
            const roomIds = [...form.querySelectorAll('input[name="room_ids[]"]:checked')].map(input => Number(input.value));
            try {
                await dfApi(@json(route('superadmin.admins.store')), {
                    method: 'POST',
                    body: { name: form.name.value, email: form.email.value, password: form.password.value, role: 'admin', room_ids: roomIds },
                });
                window.location.reload();
            } catch (error) {
                showError(error.message);
            }
        });

        document.addEventListener('click', (event) => {
            const blockBtn = event.target.closest('[data-action="block-admin"]');
            if (!blockBtn) return;
            openSuperadminConfirm({
                message: @js(__('superadmin.admins.confirm_block')).replace(':name', blockBtn.dataset.adminName),
                confirmLabel: @js(__('superadmin.admins.block_account')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/admins/${blockBtn.dataset.adminId}/status`, { method: 'PATCH', body: { status: 'blocked' } });
                    adminNotice(@js(__('superadmin.admins.updated')));
                    window.setTimeout(() => window.location.reload(), 500);
                },
            });
        });
    </script>
@endpush
