@extends('superadmin.layout', ['title' => __('platform.packages.title'), 'active' => 'packages'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('platform.nav.plans') }}</p>
            <h1>{{ __('platform.packages.title') }}</h1>
            <p>{{ __('platform.packages.description') }}</p>
        </div>
        @can('package.manage')
            <button class="sa-button" type="button" data-modal-open="create-package-modal">
                <span class="material-symbols-outlined text-[16px]">add</span>{{ __('platform.packages.create') }}
            </button>
        @endcan
    </div>
    <x-superadmin.flash />
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('platform.packages.list') }}</h2>
                <p>{{ __('platform.packages.count', ['count' => $packages->count()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <select name="status" class="sa-input" aria-label="{{ __('superadmin.common.status') }}">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (\App\Enums\PackageStatus::cases() as $case)
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
                        <th>{{ __('platform.packages.package') }}</th>
                        <th class="text-right">{{ __('platform.packages.field_monthly_price') }}</th>
                        <th class="text-right">{{ __('platform.packages.field_room_limit') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th class="text-right">{{ __('platform.packages.active_subscriptions') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td>
                                <strong class="block">{{ $package->name }}</strong>
                                <small class="block text-outline font-mono">{{ $package->code }}</small>
                            </td>
                            <td class="text-right whitespace-nowrap">{{ \App\Support\Helpers\FormatHelper::formatCurrency($package->monthly_price) }}</td>
                            <td class="text-right">{{ $package->room_limit }}</td>
                            <td><x-superadmin.status-pill :status="$package->status" /></td>
                            <td class="text-right">{{ $package->active_subscriptions_count }}</td>
                            <td class="text-right">
                                <a class="sa-button secondary" href="{{ route('superadmin.packages.show', $package) }}"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ __('superadmin.common.details') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-superadmin.empty-state icon="inventory_2" :title="__('platform.packages.empty_title')" :description="__('platform.packages.empty_description')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @can('package.manage')
        <x-superadmin.modal id="create-package-modal" icon="inventory_2" :title="__('platform.packages.create')" :description="__('platform.packages.create_description')" max-width="max-w-2xl">
            <form method="POST" action="{{ route('superadmin.packages.store') }}" class="space-y-4">
                @csrf
                @include('superadmin.packages._fields', ['package' => null])
                <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                    <button type="button" class="sa-button secondary" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                    <button type="submit" class="sa-button"><span class="material-symbols-outlined text-[16px]">add</span>{{ __('platform.packages.create') }}</button>
                </div>
            </form>
        </x-superadmin.modal>
    @endcan
@endsection
