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
            <form method="POST" action="{{ route('superadmin.superadmins.permissions', $superadmin) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <fieldset class="space-y-4" @disabled(! $canManage)>
                    @foreach ($permissionGroups as $group => $permissions)
                        <div class="border border-outline-variant rounded-xl p-3">
                            <h3 class="text-xs font-bold text-on-surface mb-2">{{ $permissions->first()->groupLabel() }}</h3>
                            <div class="space-y-1.5">
                                @foreach ($permissions as $permission)
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label class="flex items-center gap-2 text-xs text-on-surface cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->value }}" @checked(array_key_exists($permission->value, $grants))>
                                            <span>{{ $permission->label() }} <code class="text-[10px] text-outline">{{ $permission->value }}</code></span>
                                        </label>
                                        @if ($permission->supportsScope())
                                            <select name="scopes[{{ $permission->value }}]" class="sa-input !min-w-0 !py-1 text-xs" aria-label="{{ __('superadmin.superadmins.scope') }}">
                                                @foreach (\App\Enums\PermissionScope::cases() as $scope)
                                                    <option value="{{ $scope->value }}" @selected(($grants[$permission->value] ?? 'all') === $scope->value)>{{ __('superadmin.permissions.scopes.'.$scope->value) }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </fieldset>
                @if ($canManage)
                    <div class="flex justify-end">
                        <button type="submit" class="sa-button"><span class="material-symbols-outlined text-[16px]">verified_user</span>{{ __('superadmin.superadmins.save_permissions') }}</button>
                    </div>
                @endif
            </form>
        </section>
    </div>
@endsection
