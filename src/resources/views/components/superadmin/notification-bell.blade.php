{{-- Header bell: unread notifications addressed to the signed-in account (data from SuperadminLayoutComposer).
     Behaviour (toggle, mark one / all as read) lives in resources/js/superadmin/notifications.js. --}}
@props([
    'notifications',
    'presentations' => [],
    'unreadCount' => 0,
])

<div class="sa-bell" data-sa-notifications
    data-read-url-template="{{ route('superadmin.admin-notifications.read', ['notification' => '__ID__']) }}"
    data-mark-all-url="{{ route('superadmin.admin-notifications.read-all') }}">
    <button type="button" class="icon-button sa-bell-toggle" data-sa-notifications-toggle aria-expanded="false"
        aria-controls="sa-notifications-menu" aria-haspopup="true"
        title="{{ __('superadmin.inbox.unread') }}" aria-label="{{ __('superadmin.inbox.unread') }}">
        <span class="material-symbols-outlined">notifications</span>
        <span class="sa-bell-badge" data-sa-unread-badge @if($unreadCount < 1) hidden @endif>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
    </button>

    <div id="sa-notifications-menu" class="sa-bell-menu" data-sa-notifications-menu hidden>
        <div class="sa-bell-head">
            <strong>{{ __('superadmin.inbox.unread') }}</strong>
            <button type="button" class="sa-bell-link" data-sa-mark-all-read @if($unreadCount < 1) hidden @endif>{{ __('superadmin.inbox.mark_all_read') }}</button>
        </div>
        <div class="sa-bell-list" data-sa-notifications-list>
            @foreach($notifications as $notification)
                @php($presentation = $presentations[$notification->id] ?? ['title' => $notification->title, 'body' => $notification->body, 'icon' => 'notifications'])
                <button type="button" class="sa-bell-item" data-sa-notification-item data-id="{{ $notification->id }}">
                    <span class="sa-bell-icon"><span class="material-symbols-outlined">{{ $presentation['icon'] }}</span></span>
                    <span class="sa-bell-text">
                        <strong>{{ $presentation['title'] }}</strong>
                        @if($presentation['body'])<small>{{ $presentation['body'] }}</small>@endif
                        <em>{{ $notification->room?->name ? $notification->room->name.' · ' : '' }}{{ $notification->created_at?->diffForHumans() }}</em>
                    </span>
                </button>
            @endforeach
            <div data-sa-notifications-empty @if($notifications->isNotEmpty()) hidden @endif>
                <x-superadmin.empty-state icon="notifications_off" :bordered="false" :title="__('superadmin.inbox.no_unread_title')" :description="__('superadmin.inbox.no_unread_description')" />
            </div>
        </div>
        <a class="sa-bell-footer" href="{{ route('superadmin.notifications.page') }}#inbox">{{ __('superadmin.inbox.view_all') }}</a>
    </div>
</div>
