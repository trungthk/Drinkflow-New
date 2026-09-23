@extends('superadmin.layout', ['title' => __('superadmin.campaigns.title'), 'active' => 'campaigns'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.platform_data') }}</p>
            <h1>{{ __('superadmin.campaigns.title') }}</h1>
            <p>{{ __('superadmin.campaigns.description') }}</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.campaigns.registry') }}</h2>
                <p>{{ __('superadmin.common.campaigns_count', ['count' => $campaigns->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <input name="q" value="{{ $filters['search'] }}" class="sa-input" placeholder="{{ __('superadmin.campaigns.search') }}">
                <select name="room_id" class="sa-input" aria-label="{{ __('superadmin.common.room') }}">
                    <option value="">{{ __('superadmin.campaigns.all_rooms') }}</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected($filters['room_id'] === $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="sa-input" aria-label="{{ __('superadmin.common.status') }}">
                    <option value="">{{ __('superadmin.common.all_statuses') }}</option>
                    @foreach (\App\Enums\CampaignStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($filters['status'] === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.campaign') }}</th>
                        <th>{{ __('superadmin.common.room') }}</th>
                        <th class="whitespace-nowrap">{{ __('superadmin.campaigns.orders') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        @php
                            $status = $campaign->status instanceof \App\Enums\CampaignStatus ? $campaign->status : \App\Enums\CampaignStatus::tryFrom((string) $campaign->status);
                            // Mirrors CloseCampaignAction (active/closing only) and CampaignController::forceCancel.
                            $canForceClose = in_array($status, [\App\Enums\CampaignStatus::Active, \App\Enums\CampaignStatus::Closing], true);
                            $canForceCancel = $status !== null && ! in_array($status, [\App\Enums\CampaignStatus::Closed, \App\Enums\CampaignStatus::Cancelled, \App\Enums\CampaignStatus::Archived], true);
                        @endphp
                        <tr>
                            <td>
                                <strong class="block">{{ $campaign->name }}</strong>
                                <small class="block text-outline">{{ $campaign->restaurant }}</small>
                            </td>
                            <td>
                                <strong class="block">{{ $campaign->room?->name ?? '—' }}</strong>
                                @if($campaign->room)<small class="block text-outline">{{ $campaign->room->slug }}</small>@endif
                            </td>
                            <td class="whitespace-nowrap">{{ $campaign->orders_count }}</td>
                            <td>
                                @if($status)
                                    <span class="status-pill status-{{ $status->value }}"><span class="status-dot"></span>{{ $status->label() }}</span>
                                @else
                                    {{ (string) $campaign->status }}
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    @if($canForceClose)
                                        <button class="sa-button warning" type="button" data-action="force-close" data-campaign-id="{{ $campaign->id }}" data-campaign-name="{{ $campaign->name }}"><span class="material-symbols-outlined text-[16px]">stop_circle</span>{{ __('superadmin.campaigns.force_close') }}</button>
                                    @endif
                                    @if($canForceCancel)
                                        <button class="sa-button danger" type="button" data-action="force-cancel" data-campaign-id="{{ $campaign->id }}" data-campaign-name="{{ $campaign->name }}"><span class="material-symbols-outlined text-[16px]">cancel</span>{{ __('superadmin.campaigns.force_cancel') }}</button>
                                    @endif
                                    @if(! $canForceClose && ! $canForceCancel)
                                        <span class="text-outline">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        @php($isFiltered = $filters['search'] !== '' || $filters['room_id'] !== null || $filters['status'] !== '')
                        <tr>
                            <td colspan="5">
                                @if($isFiltered)
                                    <x-superadmin.empty-state icon="manage_search" :title="__('superadmin.campaigns.no_results_title')" :description="__('superadmin.campaigns.no_results_description')" />
                                @else
                                    <x-superadmin.empty-state icon="campaign" :title="__('superadmin.campaigns.no_campaigns_title')" :description="__('superadmin.campaigns.no_campaigns_description')" />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $campaigns->links() }}</div>
    </section>
@endsection
@push('scripts')
    <script>
        const campaignNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        const campaignActions = {
            'force-close': {
                endpoint: 'force-close',
                message: @js(__('superadmin.campaigns.confirm_force_close')),
                description: @js(__('superadmin.campaigns.force_close_description')),
                confirmLabel: @js(__('superadmin.campaigns.force_close')),
                confirmIcon: 'stop_circle',
                done: @js(__('superadmin.campaigns.force_closed')),
            },
            'force-cancel': {
                endpoint: 'force-cancel',
                message: @js(__('superadmin.campaigns.confirm_force_cancel')),
                description: @js(__('superadmin.campaigns.force_cancel_description')),
                confirmLabel: @js(__('superadmin.campaigns.force_cancel')),
                confirmIcon: 'cancel',
                done: @js(__('superadmin.campaigns.force_cancelled')),
            },
        };

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-action="force-close"], [data-action="force-cancel"]');
            if (!button) return;
            const action = campaignActions[button.dataset.action];
            openSuperadminConfirm({
                message: action.message.replace(':name', button.dataset.campaignName),
                description: action.description,
                confirmLabel: action.confirmLabel,
                confirmIcon: action.confirmIcon,
                onConfirm: async () => {
                    await dfApi(`/superadmin/campaigns/${button.dataset.campaignId}/${action.endpoint}`, { method: 'POST' });
                    campaignNotice(action.done);
                    window.setTimeout(() => window.location.reload(), 500);
                },
            });
        });
    </script>
@endpush
