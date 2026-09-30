@extends('superadmin.layout', ['title' => __('platform.roles.title'), 'active' => 'roles'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('platform.nav.governance') }}</p>
            <h1>{{ __('platform.roles.title') }}</h1>
            <p>{{ __('platform.roles.description') }}</p>
        </div>
        @can('superadmin.manage')
            <a class="sa-button" href="{{ route('superadmin.roles.create') }}"><span class="material-symbols-outlined text-[16px]">add</span>{{ __('platform.roles.create') }}</a>
        @endcan
    </div>
    <x-superadmin.flash />
    <section class="sa-card sa-section">
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('platform.roles.field_name') }}</th>
                        <th class="text-right">{{ __('superadmin.superadmins.permissions') }}</th>
                        <th class="text-right">{{ __('platform.roles.superadmins') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td><strong class="block">{{ $role->name }}</strong><small class="block text-outline font-mono">{{ $role->code }}</small>@if ($role->description)<small class="block text-outline">{{ $role->description }}</small>@endif</td>
                            <td class="text-right">{{ $role->permissions_count }}</td>
                            <td class="text-right">{{ $role->superadmins_count }}</td>
                            <td class="text-right"><a class="sa-button secondary" href="{{ route('superadmin.roles.edit', $role) }}"><span class="material-symbols-outlined text-[16px]">edit</span>{{ __('superadmin.common.details') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-superadmin.empty-state icon="badge" :title="__('platform.roles.empty')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
