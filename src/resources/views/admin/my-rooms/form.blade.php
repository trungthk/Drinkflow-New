@php
    $editing = $room !== null;
    $title = $editing ? __('platform.rooms.edit_title', ['name' => $room->name]) : __('platform.rooms.create');
    $label = 'block text-xs font-semibold text-on-surface mb-1';
    $input = 'w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none';
    $blocked = ! $editing && ! ($usage['has_subscription'] && $usage['remaining'] > 0);
@endphp
<x-admin.layout :title="$title" active="my-rooms" :breadcrumb="__('platform.rooms.title')">
    <div class="max-w-3xl mx-auto space-y-6">
        <section>
            <a href="{{ route('admin.rooms.index') }}" class="text-xs font-semibold text-primary hover:underline">← {{ __('platform.rooms.title') }}</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-on-surface">{{ $title }}</h1>
        </section>

        @if ($errors->any())
            <div class="rounded-lg border border-error/30 bg-error-container px-4 py-3 text-sm text-on-error-container" role="alert">{{ $errors->first() }}</div>
        @endif

        @unless ($editing)
            <x-admin.room-quota :usage="$usage" />
        @endunless

        <form method="POST" action="{{ $editing ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" data-loading-form="true"
            class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5 space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-4" @disabled($blocked)>
                <div class="sm:col-span-2">
                    <label for="room-name" class="{{ $label }}">{{ __('validation.attributes.name') }}</label>
                    <input id="room-name" name="name" type="text" required maxlength="255" value="{{ old('name', $room?->name) }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label for="room-slug" class="{{ $label }}">{{ __('validation.attributes.slug') }}</label>
                    <input id="room-slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $room?->slug) }}" class="{{ $input }} font-mono" placeholder="team-marketing">
                    <p class="mt-1 text-[11px] text-outline">{{ __('platform.rooms.slug_hint') }}</p>
                </div>
                <div>
                    <label for="room-timezone" class="{{ $label }}">{{ __('validation.attributes.timezone') }}</label>
                    <select id="room-timezone" name="timezone" class="{{ $input }}">
                        @foreach (['Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'Asia/Singapore', 'Asia/Tokyo', 'UTC'] as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $room?->timezone ?? 'Asia/Ho_Chi_Minh') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="room-language" class="{{ $label }}">{{ __('validation.attributes.language') }}</label>
                    <select id="room-language" name="language" class="{{ $input }}">
                        @foreach (\App\Constants\AppLocale::SUPPORTED as $code => $meta)
                            <option value="{{ $code }}" @selected(old('language', $room?->language ?? app()->getLocale()) === $code)>{{ $meta['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($editing)
                    <div>
                        <label for="room-status" class="{{ $label }}">{{ __('validation.attributes.status') }}</label>
                        <select id="room-status" name="status" class="{{ $input }}">
                            @foreach ([\App\Enums\RoomStatus::Active, \App\Enums\RoomStatus::Inactive] as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $room->status->value) === $status->value)>{{ __('platform.rooms.status.'.$status->value) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-outline">{{ __('platform.rooms.disabled_hint') }}</p>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <label for="room-description" class="{{ $label }}">{{ __('validation.attributes.description') }}</label>
                    <textarea id="room-description" name="description" rows="3" maxlength="2000" class="{{ $input }}">{{ old('description', $room?->description) }}</textarea>
                </div>
            </fieldset>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.rooms.index') }}" class="h-10 px-4 inline-flex items-center rounded-lg border border-outline-variant text-sm font-semibold hover:bg-surface-container">{{ __('admin.cancel') }}</a>
                <button type="submit" class="h-10 px-4 rounded-lg bg-primary text-on-primary text-sm font-semibold hover:bg-primary-container disabled:opacity-50 cursor-pointer" @disabled($blocked)>
                    {{ $editing ? __('platform.rooms.save') : __('platform.rooms.create') }}
                </button>
            </div>
        </form>
    </div>
</x-admin.layout>
