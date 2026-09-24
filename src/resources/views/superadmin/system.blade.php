@extends('superadmin.layout', ['title' => __('superadmin.system.title'), 'active' => 'system'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.infra_security') }}</p>
            <h1>{{ __('superadmin.system.title') }}</h1>
            <p>{{ __('superadmin.system.description') }}</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.dashboard.system_health') }}</h2>
                <p>{{ __('superadmin.dashboard.health_description') }}</p>
            </div>
            <button class="sa-button secondary" type="button" data-modal-open="mail-test-modal"><span class="material-symbols-outlined text-[16px]">outgoing_mail</span>{{ __('superadmin.system.send_test_mail') }}</button>
        </div>
        <div id="system-health" class="sa-health-list">
            <x-superadmin.empty-state loading :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" />
        </div>
    </section>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.system.maintenance_mode') }}</h2>
                <p>{{ __('superadmin.system.maintenance_description') }}</p>
            </div>
        </div>
        {{-- data-no-loading: the spinner belongs to the confirm modal, not to this form's first submit. --}}
        <form id="maintenance-form" class="sa-health-list" data-no-loading>
            <label class="sa-health-row">
                <span><strong>{{ __('superadmin.system.enable_maintenance') }}</strong><small>{{ __('superadmin.system.superadmin_access') }}</small></span>
                <input id="maintenance-enabled" type="checkbox">
            </label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:max-w-xl">
                <label for="maintenance-starts" class="flex flex-col gap-1 text-xs font-semibold">{{ __('superadmin.system.starts_at') }}
                    <input id="maintenance-starts" class="sa-input !min-w-0 w-full !py-2" type="datetime-local">
                </label>
                <label for="maintenance-ends" class="flex flex-col gap-1 text-xs font-semibold">{{ __('superadmin.system.ends_at') }}
                    <input id="maintenance-ends" class="sa-input !min-w-0 w-full !py-2" type="datetime-local">
                </label>
            </div>
            <div>
                <button class="sa-button" type="submit"><span class="material-symbols-outlined text-[16px]">construction</span>{{ __('superadmin.system.save_maintenance') }}</button>
            </div>
        </form>
    </section>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.system.danger_zone') }}</h2>
                <p>{{ __('superadmin.system.reset_description') }}</p>
            </div>
            <button class="sa-button danger" type="button" data-modal-open="reset-system-modal"><span class="material-symbols-outlined text-[16px]">restart_alt</span>{{ __('superadmin.system.reset') }}</button>
        </div>
    </section>

    <x-superadmin.modal id="mail-test-modal" icon="outgoing_mail" :title="__('superadmin.system.send_test_mail')" :description="__('superadmin.system.mail_test_modal_description', ['driver' => config('mail.default')])" max-width="max-w-md">
        <form id="mail-test-form" class="space-y-4">
            @csrf
            <div>
                <label for="mail-test-email" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.system.mail_test_email') }} <span class="text-error">*</span></label>
                <input type="email" id="mail-test-email" name="email" required maxlength="255" autocomplete="email" value="{{ request()->user('admin')->email }}"
                    class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="mail-test-message" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.system.mail_test_message') }}</label>
                <textarea id="mail-test-message" name="message" rows="5" maxlength="{{ \App\Http\Requests\SendTestMailRequest::MESSAGE_MAX_LENGTH }}"
                    placeholder="{{ __('superadmin.system.mail_test_body', ['app' => config('app.name')]) }}"
                    class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"></textarea>
                <p class="mt-1 text-[11px] text-outline">{{ __('superadmin.system.mail_test_message_hint') }}</p>
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">send</span>{{ __('superadmin.system.mail_test_send') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>

    <x-superadmin.modal id="reset-system-modal" icon="restart_alt" :title="__('superadmin.system.reset')" :description="__('superadmin.system.reset_modal_description')" max-width="max-w-md">
        <form id="reset-system-form" class="space-y-4">
            @csrf
            <div class="flex items-start gap-2 rounded-lg p-2.5 bg-error-container/60 border border-error/30 text-error text-xs leading-relaxed">
                <span class="material-symbols-outlined text-[16px] shrink-0" aria-hidden="true">warning</span>
                <span>{{ __('superadmin.system.reset_warning') }}</span>
            </div>
            <div>
                <label for="reset-system-password" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.system.reset_password_label') }} <span class="text-error">*</span></label>
                <x-superadmin.password-input id="reset-system-password" name="password" autocomplete="current-password" required />
            </div>
            <div>
                <label for="reset-system-phrase" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.system.reset_phrase_label', ['phrase' => $resetPhrase]) }} <span class="text-error">*</span></label>
                <input type="text" id="reset-system-phrase" name="phrase" required autocomplete="off" spellcheck="false" placeholder="{{ $resetPhrase }}"
                    class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs font-mono text-on-surface focus:outline-none focus:border-error">
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" disabled class="px-4 py-2 rounded-lg bg-error hover:opacity-90 text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span>{{ __('superadmin.system.reset') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>

    {{-- Empty states cloned by the page script, so every string stays translated through __(). --}}
    <template id="tpl-load-failed"><x-superadmin.empty-state icon="error" :title="__('superadmin.common.load_failed_title')" description="" /></template>
@endsection
@push('scripts')
    <script>
        const resetPhrase = @json($resetPhrase);
        const systemNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        // Clone one of the <template> empty states, optionally overriding its description.
        const emptyStateHtml = (templateId, description = null) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = document.getElementById(templateId).innerHTML;
            if (description !== null) wrapper.querySelector('[data-empty-description]').textContent = description;
            return wrapper.innerHTML;
        };
        async function loadSystem() {
            const {
                data
            } = await dfApi('{{ route('superadmin.system.index') }}');
            const maintenance = data.maintenance;
            document.querySelector('#maintenance-enabled').checked = maintenance.enabled;
            document.querySelector('#maintenance-starts').value = toDateTimeLocal(maintenance.starts_at);
            document.querySelector('#maintenance-ends').value = toDateTimeLocal(maintenance.ends_at);
            renderHealth(data.health);
        };
        const healthLabels = {!! json_encode(['database' => __('superadmin.dashboard.database'), 'queue' => __('superadmin.dashboard.queue'), 'socket' => 'Socket.IO', 'mail' => __('superadmin.dashboard.mail'), 'storage' => __('superadmin.dashboard.storage'), 'supervisor' => __('superadmin.dashboard.supervisor')], JSON_UNESCAPED_UNICODE) !!};
        const renderHealth = health => {
            const rows = [
                ['database', statusPill(health.database.status)],
                ['queue', `<small>${escapeHtml(health.queue.connection)} · ${health.queue.failed_jobs}</small>`],
                ['socket', statusPill(health.socket.status), health.socket.reason_message],
                ['mail', statusPill(health.mail.configured ? 'configured' : 'not_configured')],
                ['storage', statusPill(health.storage.status)],
            ];
            if (health.supervisor) rows.push(['supervisor', statusPill(health.supervisor.status)]);
            document.querySelector('#system-health').innerHTML = rows.map(([key, markup, hint]) =>
                `<div class="sa-health-row"><div><strong>${escapeHtml(healthLabels[key] || key)}</strong>${hint ? `<small>${escapeHtml(hint)}</small>` : ''}</div>${markup}</div>`
            ).join('');
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
        document.querySelector('#maintenance-form').addEventListener('submit', e => {
            e.preventDefault();
            const enabled = document.querySelector('#maintenance-enabled').checked;
            openSuperadminConfirm({
                message: enabled ? @js(__('superadmin.system.confirm_maintenance_enable')) : @js(__('superadmin.system.confirm_maintenance_disable')),
                description: enabled ? @js(__('superadmin.system.maintenance_enable_description')) : @js(__('superadmin.system.maintenance_disable_description')),
                confirmLabel: @js(__('superadmin.system.save_maintenance')),
                confirmIcon: 'construction',
                onConfirm: async () => {
                    await dfApi('{{ route('superadmin.system.maintenance.update') }}', {
                        method: 'PUT',
                        body: {
                            enabled,
                            starts_at: document.querySelector('#maintenance-starts').value || null,
                            ends_at: document.querySelector('#maintenance-ends').value || null
                        }
                    });
                    systemNotice(@js(__('superadmin.system.maintenance_saved')));
                },
            });
        });
        const mailTestForm = document.querySelector('#mail-test-form');
        mailTestForm.addEventListener('submit', async e => {
            e.preventDefault();
            const errorBox = document.querySelector('#mail-test-modal-error');
            errorBox.classList.add('hidden');
            try {
                const result = await dfApi('{{ route('superadmin.system.mail-test') }}', {
                    method: 'POST',
                    body: { email: mailTestForm.email.value, message: mailTestForm.message.value }
                });
                mailTestForm.message.value = '';
                window.closeSuperadminModal('mail-test-modal');
                systemNotice(result.message);
            } catch (error) {
                // Keep the modal open with what was typed so the address can be corrected and resent.
                errorBox.textContent = error.message;
                errorBox.classList.remove('hidden');
            } finally {
                restoreSubmitButton(mailTestForm);
            }
        });
        const resetForm = document.querySelector('#reset-system-form');
        const resetSubmit = resetForm.querySelector('button[type="submit"]');
        // Only allow submitting once the phrase is typed exactly; the server re-checks it anyway.
        const syncResetSubmit = () => {
            resetSubmit.disabled = resetForm.phrase.value !== resetPhrase || resetForm.password.value === '';
        };
        resetForm.phrase.addEventListener('input', syncResetSubmit);
        resetForm.password.addEventListener('input', syncResetSubmit);
        resetForm.addEventListener('submit', async e => {
            e.preventDefault();
            const errorBox = document.querySelector('#reset-system-modal-error');
            errorBox.classList.add('hidden');
            try {
                await dfApi('{{ route('superadmin.system.reset') }}', {
                    method: 'POST',
                    body: { password: resetForm.password.value, phrase: resetForm.phrase.value }
                });
                resetForm.reset();
                window.closeSuperadminModal('reset-system-modal');
                systemNotice(@js(__('superadmin.system.reset_complete')));
                loadSystem().catch(() => {});
            } catch (error) {
                errorBox.textContent = error.message;
                errorBox.classList.remove('hidden');
            } finally {
                restoreSubmitButton(resetForm);
                syncResetSubmit();
            }
        });

        loadSystem().catch(error => {
            systemNotice(error.message, 'error');
            document.querySelector('#system-health').innerHTML = emptyStateHtml('tpl-load-failed', error.message);
        });
    </script>
@endpush
