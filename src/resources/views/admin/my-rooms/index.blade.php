<x-admin.layout :title="__('platform.rooms.title')" active="my-rooms" :breadcrumb="__('platform.rooms.title')">
    @php
        $canAdd = $usage['has_subscription'] && $usage['remaining'] > 0;
    @endphp
    <div class="max-w-7xl mx-auto space-y-6">
        <section class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">{{ __('platform.rooms.title') }}</h1>
                <p class="mt-1.5 max-w-3xl text-sm text-on-surface-variant">{{ __('platform.rooms.subtitle') }}</p>
            </div>
            @if ($canAdd)
                <a href="{{ route('admin.rooms.create') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-primary text-on-primary text-sm font-semibold hover:bg-primary-container">
                    <span class="material-symbols-outlined text-[18px]">add</span>{{ __('platform.rooms.create') }}
                </a>
            @else
                <span class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-surface-container text-outline text-sm font-semibold cursor-not-allowed" aria-disabled="true" title="{{ $usage['has_subscription'] ? __('platform.rooms.quota_reached', ['limit' => $usage['limit']]) : __('platform.rooms.no_subscription') }}">
                    <span class="material-symbols-outlined text-[18px]">block</span>{{ __('platform.rooms.create') }}
                </span>
            @endif
        </section>

        @if (session('status'))
            <div class="rounded-lg border border-primary/30 bg-primary/10 px-4 py-3 text-sm text-on-primary-fixed-variant" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-error/30 bg-error-container px-4 py-3 text-sm text-on-error-container" role="alert">{{ $errors->first() }}</div>
        @endif

        <x-admin.room-quota :usage="$usage" class="max-w-xl" />

        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant">
                <h2 class="text-sm font-bold text-on-surface">{{ __('platform.rooms.owned') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider text-outline">
                        <tr>
                            <th class="px-5 py-3 text-left">{{ __('platform.rooms.room') }}</th>
                            <th class="px-5 py-3 text-left">{{ __('validation.attributes.status') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('platform.rooms.members') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('platform.rooms.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        @forelse ($ownedRooms as $room)
                            @php $archived = $room->status === \App\Enums\RoomStatus::Archived; @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <strong class="block text-on-surface">{{ $room->name }}</strong>
                                    <small class="block text-outline font-mono">/{{ $room->slug }}</small>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $room->status === \App\Enums\RoomStatus::Active ? 'bg-emerald-50 text-emerald-800' : 'bg-surface-container text-on-surface-variant' }}">{{ __('platform.rooms.status.'.$room->status->value) }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">{{ $room->room_users_count }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($room->status === \App\Enums\RoomStatus::Active)
                                            <a href="{{ route('admin.dashboard.page', $room) }}" class="h-8 px-3 inline-flex items-center rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container">{{ __('platform.rooms.open') }}</a>
                                        @endif
                                        @if ($archived)
                                            <form method="POST" action="{{ route('admin.rooms.restore', $room) }}" data-loading-form="true">
                                                @csrf
                                                <button type="submit" class="h-8 px-3 rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container cursor-pointer" @disabled(! $canAdd)>{{ __('platform.rooms.restore') }}</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.rooms.edit', $room) }}" class="h-8 px-3 inline-flex items-center rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container">{{ __('platform.rooms.edit') }}</a>
                                            <form method="POST" action="{{ route('admin.rooms.archive', $room) }}" data-loading-form="true"
                                                onsubmit="return confirm(@js(__('platform.rooms.archive_confirm', ['name' => $room->name])))">
                                                @csrf
                                                <button type="submit" class="h-8 px-3 rounded-lg border border-error/40 text-error text-xs font-semibold hover:bg-error-container/40 cursor-pointer">{{ __('platform.rooms.archive') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-outline">{{ __('platform.rooms.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($sharedRooms->isNotEmpty())
            <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
                <h2 class="text-sm font-bold text-on-surface">{{ __('platform.rooms.shared') }}</h2>
                <p class="text-xs text-outline mb-3">{{ __('platform.rooms.shared_hint') }}</p>
                <ul class="divide-y divide-outline-variant/60">
                    @foreach ($sharedRooms as $room)
                        <li class="py-2 flex items-center justify-between gap-3 text-sm">
                            <span>{{ $room->name }} <small class="text-outline font-mono">/{{ $room->slug }}</small></span>
                            @if ($room->status === \App\Enums\RoomStatus::Active)
                                <a href="{{ route('admin.dashboard.page', $room) }}" class="text-xs font-semibold text-primary hover:underline">{{ __('platform.rooms.open') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-admin.layout>
