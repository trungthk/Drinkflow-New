@extends('superadmin.layout', ['title' => 'Failed Jobs', 'active' => 'socket'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Infra &amp; Security</p>
            <h1>Failed Queue Jobs</h1>
            <p>Retry hoặc xóa các job lỗi sau khi kiểm tra exception và payload.</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Failed jobs</h2>
                <p id="job-count">Đang tải dữ liệu...</p>
            </div>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Failed at</th>
                        <th>Queue</th>
                        <th>UUID</th>
                        <th>Exception</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="jobs-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        const jobNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadJobs() {
            const {
                data
            } = await dfApi('{{ route('superadmin.queue.failed.index') }}');
            document.querySelector('#job-count').textContent = `${data.failed_jobs.total} failed job`;
            document.querySelector('#jobs-table').innerHTML = data.failed_jobs.data.length ? data.failed_jobs.data.map(
                j =>
                `<tr><td>${escapeHtml(j.failed_at)}</td><td>${escapeHtml(j.queue)}</td><td><small>${escapeHtml(j.uuid)}</small></td><td><small>${escapeHtml((j.exception||'').slice(0,180))}</small></td><td><div class="superadmin-actions"><button class="sa-button secondary" onclick="retryJob(${j.id})">Retry</button><button class="sa-button danger" onclick="forgetJob(${j.id})">Delete</button></div></td></tr>`
                ).join('') : '<tr><td colspan="5" class="sa-empty">Queue sạch.</td></tr>';
        };
        async function retryJob(id) {
            try {
                await dfApi(`/superadmin/queue/failed/${id}/retry`, {
                    method: 'POST'
                });
                jobNotice('Đã đưa job vào queue retry.');
                loadJobs();
            } catch (e) {
                jobNotice(e.message, 'error');
            }
        }
        async function forgetJob(id) {
            if (!confirm('Xóa failed job này?')) return;
            try {
                await dfApi(`/superadmin/queue/failed/${id}`, {
                    method: 'DELETE'
                });
                jobNotice('Đã xóa failed job.');
                loadJobs();
            } catch (e) {
                jobNotice(e.message, 'error');
            }
        }
        loadJobs();
    </script>
@endpush
