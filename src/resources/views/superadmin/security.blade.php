@extends('superadmin.layout', ['title' => __('superadmin.security.title'), 'active' => 'security'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.infra_security') }}</p>
            <h1>{{ __('superadmin.security.title') }}</h1>
        </div>
    </div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.security.events') }}</h2>
                <p>{{ __('superadmin.common.events_count', ['count' => $events->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <input name="q" value="{{ $filters['search'] ?? '' }}" class="sa-input" placeholder="{{ __('superadmin.audit.filter') }}">
                <select name="severity" class="sa-input">
                    <option value="">{{ __('superadmin.security.all_severities') }}</option>
                    @foreach(['high','medium','low'] as $value)
                        <option value="{{ $value }}" @selected(($filters['severity'] ?? '') === $value)>{{ __('superadmin.common.'.$value) }}</option>
                    @endforeach
                </select>
                <select name="actor_type" class="sa-input">
                    <option value="">{{ __('superadmin.common.all_actors') }}</option>
                    <option value="admin" @selected(($filters['actor_type'] ?? '') === 'admin')>{{ __('superadmin.common.actor_admin') }}</option>
                    <option value="user" @selected(($filters['actor_type'] ?? '') === 'user')>{{ __('superadmin.common.actor_user') }}</option>
                </select>
                <button class="sa-button secondary" type="submit">{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.time') }}</th>
                        <th>{{ __('superadmin.common.type') }}</th>
                        <th>{{ __('superadmin.security.severity') }}</th>
                        <th>IP</th>
                        <th>{{ __('superadmin.security.metadata') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>{{ $event->created_at }}</td>
                            <td><strong>{{ $event->type }}</strong></td>
                            <td><x-superadmin.status-pill :status="$event->severity" /></td>
                            <td>{{ $event->ip_address ?: '—' }}</td>
                            <td><small>{{ json_encode($event->metadata ?? []) }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sa-empty">{{ __('superadmin.security.no_events') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $events->links() }}</div>
    </section>
@endsection
