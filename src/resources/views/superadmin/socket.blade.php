@extends('superadmin.layout', ['title' => __('superadmin.socket.title'), 'active' => 'socket'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.infra_security') }}</p>
            <h1>{{ __('superadmin.socket.title') }}</h1>
            <p>{{ __('superadmin.socket.description') }}</p>
        </div><a class="sa-button secondary" href="{{ route('superadmin.queue.page') }}">{{ __('superadmin.socket.open_failed_jobs') }}</a>
    </div>
    <section class="sa-grid kpis kpis-4">
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.socket.gateway') }}</span><strong id="gateway-status"
                class="value">—</strong><span class="hint">{{ __('superadmin.socket.gateway_health') }}</span></article>
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.socket.users_online') }}</span><strong id="connected-users"
                class="value">—</strong><span class="hint">{{ __('superadmin.socket.live_connections') }}</span></article>
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.socket.admins_online') }}</span><strong id="connected-admins"
                class="value">—</strong><span class="hint">{{ __('superadmin.socket.admin_superadmin') }}</span></article>
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.socket.auth_failures') }}</span><strong id="auth-failures"
                class="value">—</strong><span class="hint">{{ __('superadmin.socket.rejected_tokens') }}</span></article>
    </section>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.socket.recent_disconnects') }}</h2>
                <p>{{ __('superadmin.socket.telemetry_only') }}</p>
            </div>
        </div>
        <div id="disconnects" class="sa-health-list">
            <x-superadmin.empty-state loading :bordered="false" :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" />
        </div>
    </section>

    {{-- Empty states cloned by the page script, so every string stays translated through __(). --}}
    <template id="tpl-no-disconnects"><x-superadmin.empty-state icon="wifi_off" :bordered="false" :title="__('superadmin.socket.no_disconnects_title')" :description="__('superadmin.socket.no_disconnects_description')" /></template>
    <template id="tpl-load-failed"><x-superadmin.empty-state icon="error" :bordered="false" :title="__('superadmin.common.load_failed_title')" description="" /></template>
@endsection
@push('scripts')
    <script>
        const renderEmpty = (container, templateId, description = null) => {
            container.innerHTML = document.getElementById(templateId).innerHTML;
            if (description !== null) container.querySelector('[data-empty-description]').textContent = description;
        };

        async function loadSocket() {
            const {
                data
            } = await dfApi('{{ route('superadmin.socket.index') }}');
            document.querySelector('#gateway-status').textContent = data.status;
            document.querySelector('#connected-users').textContent = data.connected_users ?? '—';
            document.querySelector('#connected-admins').textContent = (data.connected_admins ?? 0) + (data
                .connected_superadmins ?? 0);
            document.querySelector('#auth-failures').textContent = data.authentication_failures ?? '—';
            const disconnects = document.querySelector('#disconnects');
            if ((data.recent_disconnects || []).length) {
                disconnects.innerHTML = data.recent_disconnects.map(d =>
                    `<div class="sa-health-row"><div><strong>${escapeHtml(d.actor_type)}</strong><small>${escapeHtml(d.reason)} · ${escapeHtml(d.at)}</small></div></div>`
                    ).join('');
            } else {
                renderEmpty(disconnects, 'tpl-no-disconnects');
            }
        }
        loadSocket().catch(error => renderEmpty(document.querySelector('#disconnects'), 'tpl-load-failed', error.message));
        setInterval(() => loadSocket().catch(() => {}), 30000);
    </script>
@endpush
