@extends('superadmin.layout', ['title' => __('superadmin.superadmins.title'), 'active' => 'superadmins'])
@section('content')
    @php
        $fieldLabel = 'flex flex-col gap-1 text-xs font-semibold';
        $fieldInput = 'sa-input !min-w-0 w-full !py-2';
    @endphp
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.governance') }}</p>
            <h1>{{ __('superadmin.superadmins.title') }}</h1>
            <p>{{ __('superadmin.superadmins.description') }}</p>
        </div>
        @can('superadmin.manage')
            <button class="sa-button" type="button" data-modal-open="create-superadmin-modal">
                <span class="material-symbols-outlined text-[16px]">person_add</span>{{ __('superadmin.superadmins.create') }}
            </button>
        @endcan
    </div>
    <x-superadmin.flash />
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.superadmins.accounts') }}</h2>
                <p>{{ __('superadmin.common.accounts_count', ['count' => $superadmins->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <x-superadmin.search-input :value="$filters['search']" placeholder="{{ __('superadmin.superadmins.search') }}" />
                <select name="status" class="sa-input" aria-label="{{ __('superadmin.common.status') }}">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (\App\Enums\SuperadminStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($filters['status'] === $case->value)>{{ __('superadmin.common.'.$case->value) }}</option>
                    @endforeach
                </select>
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.superadmins.account') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th>{{ __('superadmin.superadmins.permissions') }}</th>
                        <th>{{ __('superadmin.superadmins.last_login') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($superadmins as $superadmin)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="superadmin-avatar shrink-0">{{ mb_strtoupper(mb_substr($superadmin->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <strong class="block truncate">{{ $superadmin->name }}</strong>
                                        <small class="block truncate text-outline">{{ $superadmin->email }}</small>
                                    </span>
                                </div>
                            </td>
                            <td><x-superadmin.status-pill :status="$superadmin->status" /></td>
                            <td class="whitespace-nowrap">{{ __('superadmin.superadmins.permissions_count', ['count' => $superadmin->permissions_count, 'total' => $permissionTotal]) }}</td>
                            <td class="whitespace-nowrap">{{ $superadmin->last_login_at?->format('d/m/Y H:i') ?? __('superadmin.superadmins.never_logged_in') }}</td>
                            <td class="text-right">
                                <a class="sa-button secondary" href="{{ route('superadmin.superadmins.show', $superadmin) }}"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.common.details') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-superadmin.empty-state icon="manage_search" :title="__('superadmin.superadmins.empty_title')" :description="__('superadmin.superadmins.empty_description')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($superadmins->hasPages())
            <div class="mt-4">{{ $superadmins->links() }}</div>
        @endif
    </section>

    @can('superadmin.manage')
        <x-superadmin.modal id="create-superadmin-modal" icon="person_add" :title="__('superadmin.superadmins.create')" :description="__('superadmin.superadmins.create_description')">
            <form method="POST" action="{{ route('superadmin.superadmins.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_name') }}
                        <input name="name" type="text" required maxlength="255" value="{{ old('name') }}" class="{{ $fieldInput }}">
                    </label>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_email') }}
                        <input name="email" type="email" required maxlength="255" value="{{ old('email') }}" class="{{ $fieldInput }}">
                    </label>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_password') }}
                        <x-superadmin.password-input id="create-superadmin-password" name="password" required minlength="8" />
                    </label>
                    <label class="{{ $fieldLabel }}">{{ __('superadmin.superadmins.field_password_confirmation') }}
                        <x-superadmin.password-input id="create-superadmin-password-confirmation" name="password_confirmation" required minlength="8" />
                    </label>
                </div>
                <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                    <button type="button" class="sa-button secondary" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                    <button type="submit" class="sa-button"><span class="material-symbols-outlined text-[16px]">person_add</span>{{ __('superadmin.superadmins.create') }}</button>
                </div>
            </form>
        </x-superadmin.modal>
    @endcan
@endsection
