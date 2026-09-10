@extends('superadmin.layout', ['title' => 'Version Management', 'active' => 'versions'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Release Operations</p>
            <h1>Version Management</h1>
            <p>Quản lý release note, cờ important và yêu cầu force refresh cho client.</p>
        </div><button class="sa-button" onclick="createVersion()"><span
                class="material-symbols-outlined">new_releases</span>New release</button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Release history</h2>
                <p id="version-count">Đang tải dữ liệu...</p>
            </div>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Title</th>
                        <th>Release date</th>
                        <th>Flags</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="versions-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        const versionNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadVersions() {
            const {
                data
            } = await dfApi('{{ route('superadmin.versions.index') }}');
            document.querySelector('#version-count').textContent = `${data.total} releases`;
            document.querySelector('#versions-table').innerHTML = data.data.length ? data.data.map(v =>
                `<tr><td><strong>${escapeHtml(v.version)}</strong></td><td>${escapeHtml(v.title)}</td><td>${escapeHtml(v.release_date||'—')}</td><td>${v.important?'<span class="status-pill status-pending">Important</span>':''} ${v.force_refresh?'<span class="status-pill status-active">Force refresh</span>':''}</td><td><button class="sa-button danger" onclick="deleteVersion(${v.id})">Delete</button></td></tr>`
                ).join('') : '<tr><td colspan="5" class="sa-empty">Chưa có release.</td></tr>';
        };
        async function createVersion() {
            const version = prompt('Version, ví dụ v2.1.0');
            if (!version) return;
            const title = prompt('Release title');
            if (!title) return;
            const changelog = prompt('Changelog');
            try {
                await dfApi('{{ route('superadmin.versions.store') }}', {
                    method: 'POST',
                    body: {
                        version,
                        title,
                        changelog,
                        release_date: new Date().toISOString().slice(0, 10),
                        important: false,
                        force_refresh: false
                    }
                });
                versionNotice('Đã tạo release.');
                loadVersions();
            } catch (e) {
                versionNotice(e.message, 'error');
            }
        }
        async function deleteVersion(id) {
            if (!confirm('Xóa release này?')) return;
            try {
                await dfApi(`/superadmin/versions/${id}`, {
                    method: 'DELETE'
                });
                loadVersions();
            } catch (e) {
                versionNotice(e.message, 'error');
            }
        }
        loadVersions();
    </script>
@endpush
