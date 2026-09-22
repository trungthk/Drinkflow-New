@extends('superadmin.layout', ['title' => __('superadmin.audit.title'), 'active' => 'audit'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.platform_data') }}</p>
            <h1>{{ __('superadmin.audit.title') }}</h1>
        </div>
    </div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.audit.events') }}</h2>
                <p>{{ __('superadmin.common.events_count', ['count' => $audits->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <input name="event" value="{{ $filters['event'] ?? '' }}" class="sa-input" placeholder="{{ __('superadmin.audit.filter') }}">
                <select name="actor_type" class="sa-input">
                    <option value="">{{ __('superadmin.common.all_actors') }}</option>
                    <option value="admin" @selected(($filters['actor_type'] ?? '') === 'admin')>{{ __('superadmin.common.actor_admin') }}</option>
                    <option value="user" @selected(($filters['actor_type'] ?? '') === 'user')>{{ __('superadmin.common.actor_user') }}</option>
                </select>
                <x-admin.date-range-filter id="audit-date-range" :dateFrom="$filters['date_from'] ?? ''" :dateTo="$filters['date_to'] ?? ''" />
                <button class="sa-button secondary" type="submit">{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.time') }}</th>
                        <th>{{ __('superadmin.audit.actor') }}</th>
                        <th>{{ __('superadmin.audit.event') }}</th>
                        <th>{{ __('superadmin.audit.target') }}</th>
                        <th>{{ __('superadmin.common.room') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audits as $audit)
                        <tr>
                            <td>{{ $audit->created_at }}</td>
                            <td>{{ $audit->actor_type }} #{{ $audit->actor_id ?: __('superadmin.common.system') }}</td>
                            <td><strong>{{ $audit->event }}</strong></td>
                            <td>{{ $audit->target_type }} #{{ $audit->target_id ?: '—' }}</td>
                            <td>{{ $audit->room?->name ?? __('superadmin.common.global') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sa-empty">{{ __('superadmin.audit.no_events') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $audits->links() }}</div>
    </section>
@endsection
