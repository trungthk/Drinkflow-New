@extends('superadmin.layout', ['title' => $superadmin->name, 'active' => 'superadmins'])
@section('content')
    @php
        $fieldLabel = 'flex flex-col gap-1 text-xs font-semibold';
        $fieldInput = 'sa-input !min-w-0 w-full !py-2';
        $canManage = \Illuminate\Support\Facades\Gate::allows('superadmin.manage');
        $isSelf = $superadmin->is(request()->user('superadmin'));
    @endphp
    <div class="superadmin-heading">
        <div>
            <a class="sa-back-link" href="{{ route('superadmin.superadmins.index') }}">← {{ __('superadmin.superadmins.title') }}</a>
            <h1>{{ $superadmin->name }}</h1>
            <p>{{ $superadmin->email }} · <x-superadmin.status-pill :status="$superadmin->status" /></p>
        </div>
        @if ($canManage && ! $isSelf)
            <form method="POST" action="{{ route('superadmin.superadmins.destroy', $superadmin) }}"
                data-confirm="{{ __('superadmin.superadmins.delete_confirm', ['name' => $superadmin->name]) }}">
                @csrf
                @method('DELETE')
                <button class="sa-button danger" type="submit"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.common.delete') }}</button>
            </form>
        @endif
    </div>
    <x-superadmin.flash />

    <div class="sa-split">
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.superadmins.profile') }}</h2>
                    <p>{{ __('superadmin.superadmins.profile_description') }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('superadmin.superadmins.update', $superadmin) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-4" @disabled(! $canManage)>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_name') }}
                        <input name="name" type="text" required maxlength="255" value="{{ old('name', $superadmin->name) }}" class="{{ $fieldInput }}">
                    </label>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_email') }}
                        <input name="email" type="email" required maxlength="255" value="{{ old('email', $superadmin->email) }}" class="{{ $fieldInput }}">
                    </label>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.common.status') }}
                        <select name="status" class="{{ $fieldInput }}">
                            @foreach (\App\Enums\SuperadminStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected(old('status', $superadmin->status->value) === $case->value)>{{ __('superadmin.common.'.$case->value) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div></div>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_new_password') }}
                        <x-superadmin.password-input id="superadmin-password" name="password" minlength="8" />
                    </label>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_password_confirmation') }}
                        <x-superadmin.password-input id="superadmin-password-confirmation" name="password_confirmation" minlength="8" />
                    </label>
                </fieldset>
                @if ($canManage)
                    <div class="flex justify-end">
                        <button type="submit" class="sa-button"><span class="material-symbols-outlined text-[16px]">save</span>{{ __('superadmin.common.save') }}</button>
                    </div>
                @endif
            </form>
        </section>

        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.superadmins.permissions') }}</h2>
                    <p>{{ __('superadmin.superadmins.permissions_description') }}</p>
                </div>
            </div>
            @php($roles = \App\Models\SuperadminRole::query()->orderBy('name')->get(['id', 'name']))
            @if ($canManage && $roles->isNotEmpty())
                <form method="POST" action="{{ route('superadmin.superadmins.role', $superadmin) }}" class="flex flex-wrap items-end gap-2 mb-4 pb-4 border-b border-outline-variant"
                    data-confirm="{{ __('platform.roles.apply_confirm') }}">
                    @csrf
                    <label class="flex flex-col gap-1 text-xs font-semibold flex-1 min-w-[200px]">{{ __('platform.roles.apply_label') }}
                        <select name="role_id" class="sa-input !min-w-0 w-full !py-2">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected($superadmin->superadmin_role_id === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="sa-button secondary"><span class="material-symbols-outlined text-[16px]">badge</span>{{ __('platform.roles.apply') }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route('superadmin.superadmins.permissions', $superadmin) }}" class="space-y-4">
                @csrf
                @method('PUT')
                @include('superadmin.superadmins._permission-matrix')
                @if ($canManage)
                    <div class="flex justify-end">
                        <button type="submit" class="sa-button"><span class="material-symbols-outlined text-[16px]">verified_user</span>{{ __('superadmin.superadmins.save_permissions') }}</button>
                    </div>
                @endif
            </form>
        </section>
    </div>
@endsection
