@extends('superadmin.layout', ['title' => __('superadmin.notifications.title'), 'active' => 'notifications'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.system_messaging') }}</p>
            <h1>{{ __('superadmin.notifications.title') }}</h1>
        </div>
        <button class="sa-button" type="button" data-modal-open="create-channel-modal">
            <span class="material-symbols-outlined text-[16px]">add_alert</span>{{ __('superadmin.notifications.add_channel') }}
        </button>
    </div>
    <div id="notice" class="sa-notice"></div>
    {{-- Inbox: only notifications addressed to the signed-in admin account (PageController::notifications). --}}
    <section id="inbox" class="sa-card sa-section" data-sa-inbox>
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.inbox.title') }}</h2>
                <p>{{ __('superadmin.inbox.description', ['count' => $inboxUnreadCount]) }}</p>
            </div>
            <div class="superadmin-actions">
                <form method="GET" action="{{ route('superadmin.notifications.page') }}#inbox" class="superadmin-actions">
                    @if(($filters['search'] ?? '') !== '')<input type="hidden" name="q" value="{{ $filters['search'] }}">@endif
                    <select name="inbox_status" class="sa-input" aria-label="{{ __('superadmin.common.status') }}">
                        <option value="">{{ __('superadmin.inbox.status_all') }}</option>
                        <option value="{{ \App\Services\Notification\AdminNotificationService::FILTER_UNREAD }}" @selected($filters['inboxStatus'] === \App\Services\Notification\AdminNotificationService::FILTER_UNREAD)>{{ __('superadmin.inbox.status_unread') }}</option>
                        <option value="{{ \App\Services\Notification\AdminNotificationService::FILTER_READ }}" @selected($filters['inboxStatus'] === \App\Services\Notification\AdminNotificationService::FILTER_READ)>{{ __('superadmin.inbox.status_read') }}</option>
                    </select>
                    <x-superadmin.search-input name="inbox_q" :value="$filters['inboxSearch']" placeholder="{{ __('superadmin.inbox.search') }}" />
                    <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_alt</span>{{ __('superadmin.common.filter') }}</button>
                </form>
                @if($inboxUnreadCount > 0)
                    <button class="sa-button secondary" type="button" data-sa-inbox-mark-all><span class="material-symbols-outlined text-[16px]">done_all</span>{{ __('superadmin.inbox.mark_all_read') }}</button>
                @endif
            </div>
        </div>
        <div class="sa-inbox-list">
            @forelse($inbox as $notification)
                @php($presentation = $inboxPresentations[$notification->id] ?? ['title' => $notification->title, 'body' => $notification->body, 'icon' => 'notifications'])
                <div class="sa-inbox-row" data-sa-inbox-row data-id="{{ $notification->id }}" @if(!$notification->read_at) data-unread @endif>
                    <span class="sa-bell-icon"><span class="material-symbols-outlined">{{ $presentation['icon'] }}</span></span>
                    <div class="sa-inbox-text">
                        <strong>{{ $presentation['title'] }}</strong>
                        @if($presentation['body'])<p>{{ $presentation['body'] }}</p>@endif
                        <small>
                            @if($notification->room)
                                <a href="{{ route('superadmin.rooms.detail.page', $notification->room) }}">{{ $notification->room->name }}</a> ·
                            @endif
                            <time datetime="{{ $notification->created_at?->toIso8601String() }}" title="{{ $notification->created_at?->format('d/m/Y H:i') }}">{{ $notification->created_at?->diffForHumans() }}</time>
                        </small>
                    </div>
                    @if(!$notification->read_at)
                        <button class="sa-button secondary" type="button" data-sa-inbox-read><span class="material-symbols-outlined text-[16px]">done</span>{{ __('superadmin.inbox.mark_read') }}</button>
                    @endif
                </div>
            @empty
                @if($filters['inboxStatus'] !== '' || $filters['inboxSearch'] !== '')
                    <x-superadmin.empty-state icon="search_off" :title="__('superadmin.inbox.no_results_title')" :description="__('superadmin.inbox.no_results_description')" />
                @else
                    <x-superadmin.empty-state icon="notifications_off" :title="__('superadmin.inbox.empty_title')" :description="__('superadmin.inbox.empty_description')" />
                @endif
            @endforelse
        </div>
        <div class="mt-4">{{ $inbox->fragment('inbox')->links() }}</div>
    </section>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.notifications.system_channels') }}</h2>
                <p>{{ __('superadmin.common.accounts_count', ['count' => $channels->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                @if($filters['inboxStatus'] !== '')<input type="hidden" name="inbox_status" value="{{ $filters['inboxStatus'] }}">@endif
                @if($filters['inboxSearch'] !== '')<input type="hidden" name="inbox_q" value="{{ $filters['inboxSearch'] }}">@endif
                <x-superadmin.search-input :value="$filters['search'] ?? ''" placeholder="{{ __('superadmin.notifications.search') }}" />
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_alt</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.notifications.channel') }}</th>
                        <th>{{ __('superadmin.common.type') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th>{{ __('superadmin.notifications.credential') }}</th>
                        <th>{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($channels as $channel)
                        <tr>
                            <td><strong>{{ $channel->name }}</strong></td>
                            <td>{{ $channel->type }}</td>
                            <td><x-superadmin.status-pill :status="$channel->status" /></td>
                            <td>••••••••</td>
                            <td><button class="sa-button danger" type="button" data-action="delete-channel" data-channel-id="{{ $channel->id }}">{{ __('superadmin.common.delete') }}</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            @if(($filters['search'] ?? '') !== '')
                                <x-superadmin.empty-state icon="search_off" :bordered="false" :title="__('superadmin.notifications.no_results_title')" :description="__('superadmin.notifications.no_results_description')" />
                            @else
                                <x-superadmin.empty-state icon="notifications_off" :bordered="false" :title="__('superadmin.notifications.no_channels_title')" :description="__('superadmin.notifications.no_channels_description')" />
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $channels->links() }}</div>
    </section>

    <x-superadmin.modal id="create-channel-modal" icon="add_alert" :title="__('superadmin.notifications.add_channel')" :description="__('superadmin.notifications.credential_description')">
        <form id="create-channel-form" class="space-y-4">
            @csrf
            <div>
                <label for="create-channel-name" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.notifications.prompt_name') }} <span class="text-error">*</span></label>
                <input type="text" id="create-channel-name" name="name" required class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="create-channel-type" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.common.type') }} <span class="text-error">*</span></label>
                <select id="create-channel-type" name="type" required class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    <option value="slack">Slack</option>
                    <option value="telegram">Telegram</option>
                    <option value="chatwork">Chatwork</option>
                    <option value="webhook">Webhook</option>
                </select>
            </div>
            <div>
                <label for="create-channel-config" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.notifications.prompt_credential') }} <span class="text-error">*</span></label>
                <textarea id="create-channel-config" name="config" required rows="3" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"></textarea>
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">add_alert</span>{{ __('superadmin.notifications.add_channel') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>
@endsection
@push('scripts')
    <script>
        const notifNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };

        document.querySelector('#create-channel-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const errorBox = document.querySelector('#create-channel-modal-error');
            errorBox?.classList.add('hidden');
            try {
                await dfApi('{{ route('superadmin.notifications.store') }}', {
                    method: 'POST',
                    body: { name: form.name.value, type: form.type.value, config: form.config.value, status: 'active' },
                });
                window.location.reload();
            } catch (error) {
                if (errorBox) { errorBox.textContent = error.message; errorBox.classList.remove('hidden'); }
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = false;
                    button.classList.remove('opacity-75', 'cursor-wait');
                    if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
                }
            }
        });

        document.addEventListener('click', (event) => {
            const deleteBtn = event.target.closest('[data-action="delete-channel"]');
            if (!deleteBtn) return;
            openSuperadminConfirm({
                message: @js(__('superadmin.notifications.confirm_delete')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/notifications/${deleteBtn.dataset.channelId}`, { method: 'DELETE' });
                    notifNotice(@js(__('superadmin.common.delete')));
                    window.setTimeout(() => window.location.reload(), 500);
                },
            });
        });
    </script>
@endpush
