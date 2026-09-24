@extends('superadmin.layout', ['title' => __('superadmin.users.title'), 'active' => 'users'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.identity_layer') }}</p>
            <h1>{{ __('superadmin.users.title') }}</h1>
            <p>{{ __('superadmin.users.description') }}</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.users.global_users') }}</h2>
                <p>{{ trans_choice('superadmin.common.global_users_count', $users->total(), ['count' => $users->total()]) }}</p>
            </div>
            <form method="get" class="sa-filters">
                <x-superadmin.search-input :value="request('q')" placeholder="{{ __('superadmin.users.search') }}" />
                <select name="status" class="sa-input" aria-label="{{ __('superadmin.common.status') }}">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (\App\Enums\GlobalUserStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>{{ __('superadmin.common.'.$statusOption->value) }}</option>
                    @endforeach
                </select>
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.user') }}</th>
                        <th class="whitespace-nowrap">{{ __('superadmin.users.oauth') }}</th>
                        <th class="whitespace-nowrap">{{ __('superadmin.users.memberships') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $statusValue = $user->status?->value ?? (string) $user->status;
                            $isBlocked = $statusValue === \App\Enums\GlobalUserStatus::Blocked->value;
                            $isDeleted = $statusValue === \App\Enums\GlobalUserStatus::Deleted->value;
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="superadmin-avatar shrink-0">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <strong class="block truncate">{{ $user->name }}</strong>
                                        <small class="block truncate text-outline">{{ $user->email }}</small>
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">{{ $user->oauthIdentities->pluck('provider')->join(', ') ?: '—' }}</td>
                            <td class="whitespace-nowrap">{{ $user->room_users_count }}</td>
                            <td><x-superadmin.status-pill :status="$statusValue" /></td>
                            <td>
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a class="sa-button secondary" href="{{ route('superadmin.global-users.detail.page', $user) }}"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.common.details') }}</a>
                                    @unless ($isDeleted)
                                    <button class="sa-button {{ $isBlocked ? '' : 'warning' }}" type="button" data-action="toggle-user-status" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-current-status="{{ $statusValue }}">
                                        <span class="material-symbols-outlined text-[16px]">{{ $isBlocked ? 'lock_open' : 'lock' }}</span>{{ $isBlocked ? __('superadmin.common.unblock') : __('superadmin.common.block') }}
                                    </button>
                                    <button class="sa-button danger" type="button" data-action="delete-user" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.common.delete') }}</button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-superadmin.empty-state icon="person_search" :title="__('superadmin.users.no_results_title')" :description="__('superadmin.users.no_results_description')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->withQueryString()->links() }}</div>
    </section>
@endsection
@push('scripts')
    <script>
        const userNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };

        document.addEventListener('click', (event) => {
            const toggleBtn = event.target.closest('[data-action="toggle-user-status"]');
            if (toggleBtn) {
                const isBlocked = toggleBtn.dataset.currentStatus === 'blocked';
                const nextStatus = isBlocked ? 'active' : 'blocked';
                openSuperadminConfirm({
                    message: (isBlocked ? @js(__('superadmin.users.confirm_unblock')) : @js(__('superadmin.users.confirm_block'))).replace(':name', toggleBtn.dataset.userName),
                    description: isBlocked ? @js(__('superadmin.users.unblock_description')) : @js(__('superadmin.users.block_description')),
                    confirmIcon: isBlocked ? 'lock_open' : 'lock',
                    confirmLabel: isBlocked ? @js(__('superadmin.common.unblock')) : @js(__('superadmin.common.block')),
                    onConfirm: async () => {
                        await dfApi(`/superadmin/global-users/${toggleBtn.dataset.userId}/status`, { method: 'PATCH', body: { status: nextStatus } });
                        userNotice(@js(__('superadmin.users.status_updated')));
                        window.setTimeout(() => window.location.reload(), 500);
                    },
                });
                return;
            }

            const deleteBtn = event.target.closest('[data-action="delete-user"]');
            if (!deleteBtn) return;
            openSuperadminConfirm({
                message: @js(__('superadmin.users.confirm_delete')),
                confirmLabel: @js(__('superadmin.common.delete')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/global-users/${deleteBtn.dataset.userId}`, { method: 'DELETE' });
                    userNotice(@js(__('superadmin.users.deleted')));
                    window.setTimeout(() => window.location.reload(), 500);
                },
            });
        });
    </script>
@endpush
