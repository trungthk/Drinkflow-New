@extends('superadmin.layout', ['title' => __('superadmin.queue.title'), 'active' => 'socket'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.infra_security') }}</p>
            <h1>{{ __('superadmin.queue.title') }}</h1>
            <p>{{ __('superadmin.queue.description') }}</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.queue.failed_jobs') }}</h2>
                <p>{{ __('superadmin.common.failed_jobs_count', ['count' => $jobs->total()]) }}</p>
            </div>
            <form method="GET" class="superadmin-actions">
                <input name="q" value="{{ $filters['search'] ?? '' }}" class="sa-input" placeholder="{{ __('superadmin.queue.search') }}">
                <button class="sa-button secondary" type="submit"><span class="material-symbols-outlined text-[16px]">filter_list</span>{{ __('superadmin.common.filter') }}</button>
            </form>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.queue.failed_at') }}</th>
                        <th>{{ __('superadmin.queue.queue') }}</th>
                        <th>UUID</th>
                        <th>{{ __('superadmin.queue.exception') }}</th>
                        <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td class="whitespace-nowrap">{{ $job->failed_at }}</td>
                            <td class="whitespace-nowrap">{{ $job->queue }}</td>
                            <td><small>{{ $job->uuid }}</small></td>
                            <td><small>{{ Str::limit($job->exception, 180) }}</small></td>
                            <td>
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <button class="sa-button secondary" type="button" data-action="retry-job" data-job-id="{{ $job->id }}"><span class="material-symbols-outlined text-[16px]">replay</span>{{ __('superadmin.common.retry') }}</button>
                                    <button class="sa-button danger" type="button" data-action="delete-job" data-job-id="{{ $job->id }}"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.common.delete') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-superadmin.empty-state icon="task_alt" :title="__('superadmin.queue.no_results_title')" :description="__('superadmin.queue.no_results_description')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $jobs->links() }}</div>
    </section>
@endsection
@push('scripts')
    <script>
        const queueNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };

        document.addEventListener('click', (event) => {
            const retryBtn = event.target.closest('[data-action="retry-job"]');
            if (retryBtn) {
                openSuperadminConfirm({
                    message: @js(__('superadmin.queue.confirm_retry')),
                    description: @js(__('superadmin.queue.retry_description')),
                    confirmLabel: @js(__('superadmin.common.retry')),
                    confirmIcon: 'replay',
                    onConfirm: async () => {
                        await dfApi(`/superadmin/queue/failed/${retryBtn.dataset.jobId}/retry`, { method: 'POST' });
                        queueNotice(@js(__('superadmin.queue.retried')));
                        window.setTimeout(() => window.location.reload(), 500);
                    },
                });
                return;
            }

            const deleteBtn = event.target.closest('[data-action="delete-job"]');
            if (!deleteBtn) return;
            openSuperadminConfirm({
                message: @js(__('superadmin.queue.confirm_delete')),
                description: @js(__('superadmin.queue.delete_description')),
                confirmLabel: @js(__('superadmin.common.delete')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/queue/failed/${deleteBtn.dataset.jobId}`, { method: 'DELETE' });
                    queueNotice(@js(__('superadmin.queue.deleted')));
                    window.setTimeout(() => window.location.reload(), 500);
                },
            });
        });
    </script>
@endpush
