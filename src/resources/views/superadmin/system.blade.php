@extends('superadmin.layout', ['title' => 'System Settings & Maintenance', 'active' => 'system'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Infra &amp; Security</p>
            <h1>System Settings &amp; Maintenance</h1>
            <p>Cấu hình authentication cấp hệ thống, giới hạn mặc định và chế độ bảo trì.</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.dashboard.system_health') }}</h2>
                <p>{{ __('superadmin.dashboard.health_description') }}</p>
            </div>
            <form id="mail-test-form" method="post" action="{{ route('superadmin.system.mail-test') }}">
                @csrf
                <button class="sa-button secondary" type="submit">{{ __('superadmin.system.send_test_mail') }}</button>
            </form>
        </div>
        <div id="system-health" class="sa-health-list"></div>
    </section>
    <div class="sa-split">
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>Maintenance mode</h2>
                    <p>Bật ngay hoặc lập lịch theo timezone hệ thống.</p>
                </div>
            </div>
            <form id="maintenance-form" class="sa-health-list"><label class="sa-health-row"><span><strong>Enable
                            maintenance</strong><small>Superadmin vẫn được truy cập.</small></span><input
                        id="maintenance-enabled" type="checkbox"></label><label>Starts at<input id="maintenance-starts"
                        class="sa-input" type="datetime-local"></label><label>Ends at<input id="maintenance-ends"
                        class="sa-input" type="datetime-local"></label><button class="sa-button" type="submit">Save
                    maintenance</button></form>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>Typed settings</h2>
                    <p>Secret chỉ hiển thị trạng thái configured.</p>
                </div>
            </div>
            <form id="settings-form" class="sa-health-list"></form>
        </section>
    </div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Danger zone</h2>
                <p>System reset xóa dữ liệu vận hành và giữ lại superadmin.</p>
            </div>
        </div><button class="sa-button danger" onclick="resetSystem()">Reset DrinkFlow</button>
    </section>
@endsection
@push('scripts')
    <script>
        const systemNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadSystem() {
            const {
                data
            } = await dfApi('{{ route('superadmin.system.index') }}');
            const maintenance = data.maintenance;
            document.querySelector('#maintenance-enabled').checked = maintenance.enabled;
            document.querySelector('#maintenance-starts').value = toDateTimeLocal(maintenance.starts_at);
            document.querySelector('#maintenance-ends').value = toDateTimeLocal(maintenance.ends_at);
            document.querySelector('#settings-form').innerHTML = data.settings.length ? data.settings.map(setting => setting.is_secret ?
                `<div class="sa-health-row"><div><strong>${escapeHtml(setting.key)}</strong><small>Secret · ${setting.configured ? 'Configured' : 'Not configured'}</small></div><span>••••••••</span></div>` : settingControl(setting)).join('') + (data.settings.length ? '<button class="sa-button" type="submit"><span class="material-symbols-outlined">save</span>Save settings</button>' : '') : '<div class="sa-empty">Chưa có system setting.</div>';
            renderHealth(data.health);
        };
        const healthLabels = {!! json_encode(['database' => __('superadmin.dashboard.database'), 'queue' => __('superadmin.dashboard.queue'), 'socket' => 'Socket.IO', 'mail' => __('superadmin.dashboard.mail'), 'storage' => __('superadmin.dashboard.storage'), 'supervisor' => __('superadmin.dashboard.supervisor')], JSON_UNESCAPED_UNICODE) !!};
        const renderHealth = health => {
            const rows = [
                ['database', statusPill(health.database.status)],
                ['queue', `<small>${escapeHtml(health.queue.connection)} · ${health.queue.failed_jobs}</small>`],
                ['socket', statusPill(health.socket.status)],
                ['mail', statusPill(health.mail.configured ? 'configured' : 'not_configured')],
                ['storage', statusPill(health.storage.status)],
            ];
            if (health.supervisor) rows.push(['supervisor', statusPill(health.supervisor.status)]);
            document.querySelector('#system-health').innerHTML = rows.map(([key, markup]) =>
                `<div class="sa-health-row"><div><strong>${escapeHtml(healthLabels[key] || key)}</strong></div>${markup}</div>`
            ).join('');
        };
        const settingControl = setting => {
            const value = setting.type === 'json' ? JSON.stringify(setting.value ?? {}, null, 2) : setting.value ?? '';
            if (setting.type === 'boolean') return `<label class="sa-health-row"><span><strong>${escapeHtml(setting.key)}</strong><small>Boolean</small></span><input data-setting-key="${escapeHtml(setting.key)}" data-setting-type="boolean" type="checkbox" ${setting.value ? 'checked' : ''}></label>`;
            return `<label><strong>${escapeHtml(setting.key)}</strong><textarea data-setting-key="${escapeHtml(setting.key)}" data-setting-type="${escapeHtml(setting.type)}" class="sa-input sa-setting-value" rows="${setting.type === 'json' ? 3 : 1}" ${setting.type === 'integer' ? 'inputmode="numeric"' : ''}>${escapeHtml(value)}</textarea></label>`;
        };
        const toDateTimeLocal = value => {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value.slice(0, 16);
            const offset = date.getTimezoneOffset();
            return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 16);
        };
        // None of the forms on this page navigate away or reload after submit, so — unlike a normal
        // POST — the submit button the shared submit-loading listener (initSuperadminLoading) disabled
        // and spun must be restored by hand once the async request settles.
        const restoreSubmitButton = form => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
            if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
        };
        document.querySelector('#maintenance-form').addEventListener('submit', async e => {
            e.preventDefault();
            try {
                await dfApi('{{ route('superadmin.system.maintenance.update') }}', {
                    method: 'PUT',
                    body: {
                        enabled: document.querySelector('#maintenance-enabled').checked,
                        starts_at: document.querySelector('#maintenance-starts').value || null,
                        ends_at: document.querySelector('#maintenance-ends').value || null
                    }
                });
                systemNotice('Đã lưu maintenance settings.');
            } catch (error) {
                systemNotice(error.message, 'error');
            } finally {
                restoreSubmitButton(e.target);
            }
        });
        document.querySelector('#mail-test-form').addEventListener('submit', async e => {
            e.preventDefault();
            try {
                const result = await dfApi('{{ route('superadmin.system.mail-test') }}', { method: 'POST' });
                systemNotice(result.message);
            } catch (error) {
                systemNotice(error.message, 'error');
            } finally {
                restoreSubmitButton(e.target);
            }
        });
        document.querySelector('#settings-form').addEventListener('submit', async e => {
            e.preventDefault();
            try {
                const settings = [...document.querySelectorAll('[data-setting-key]')].map(input => ({
                    key: input.dataset.settingKey,
                    type: input.dataset.settingType,
                    value: input.type === 'checkbox' ? input.checked : input.value
                }));
                await dfApi('{{ route('superadmin.system.settings') }}', { method: 'PUT', body: { settings } });
                systemNotice('Đã lưu system settings.');
                await loadSystem();
            } catch (error) {
                systemNotice(error.message, 'error');
                restoreSubmitButton(e.target);
            }
        });
        async function resetSystem() {
            const password = prompt('Nhập mật khẩu superadmin');
            if (!password) return;
            const phrase = prompt('Nhập chính xác RESET DRINKFLOW');
            if (!phrase) return;
            if (!confirm('Thao tác này không thể hoàn tác. Tiếp tục?')) return;
            try {
                await dfApi('{{ route('superadmin.system.reset') }}', {
                    method: 'POST',
                    body: {
                        password,
                        phrase
                    }
                });
                systemNotice('Đã reset hệ thống.');
            } catch (error) {
                systemNotice(error.message, 'error');
            }
        }
        loadSystem();
    </script>
@endpush
