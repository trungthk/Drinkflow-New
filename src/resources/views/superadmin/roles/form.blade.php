@extends('superadmin.layout', ['title' => $role?->name ?? __('platform.roles.create'), 'active' => 'roles'])
@section('content')
    @php
        $fieldLabel = 'flex flex-col gap-1 text-xs font-semibold';
        $fieldInput = 'sa-input !min-w-0 w-full !py-2';
    @endphp
    <div class="superadmin-heading">
        <div>
            <a class="sa-back-link" href="{{ route('superadmin.roles.index') }}">← {{ __('platform.roles.title') }}</a>
            <h1>{{ $role?->name ?? __('platform.roles.create') }}</h1>
            <p>{{ __('platform.roles.form_hint') }}</p>
        </div>
        @if ($role && $canManage)
            <form method="POST" action="{{ route('superadmin.roles.destroy', $role) }}" data-confirm="{{ __('platform.roles.delete_confirm', ['name' => $role->name]) }}">
                @csrf
                @method('DELETE')
                <button class="sa-button danger" type="submit"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.common.delete') }}</button>
            </form>
        @endif
    </div>
    <x-superadmin.flash />
    <form method="POST" action="{{ $role ? route('superadmin.roles.update', $role) : route('superadmin.roles.store') }}" class="space-y-4">
        @csrf
        @if ($role)
            @method('PUT')
        @endif
        <section class="sa-card sa-section">
            <fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-4" @disabled(! $canManage)>
                <label class="{{ $fieldLabel }}">{{ __('platform.roles.field_code') }}
                    <input name="code" type="text" required maxlength="50" value="{{ old('code', $role?->code) }}" class="{{ $fieldInput }} font-mono">
                </label>
                <label class="{{ $fieldLabel }}">{{ __('platform.roles.field_name') }}
                    <input name="name" type="text" required maxlength="255" value="{{ old('name', $role?->name) }}" class="{{ $fieldInput }}">
                </label>
                <label class="{{ $fieldLabel }} sm:col-span-2">{{ __('platform.roles.field_description') }}
                    <textarea name="description" rows="2" maxlength="1000" class="{{ $fieldInput }}">{{ old('description', $role?->description) }}</textarea>
                </label>
            </fieldset>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header"><div><h2>{{ __('superadmin.superadmins.permissions') }}</h2><p>{{ __('superadmin.superadmins.permissions_description') }}</p></div></div>
            @include('superadmin.superadmins._permission-matrix')
        </section>
        @if ($canManage)
            <div class="flex justify-end">
                <button type="submit" class="sa-button"><span class="material-symbols-outlined text-[16px]">save</span>{{ __('superadmin.common.save') }}</button>
            </div>
        @endif
    </form>
@endsection
