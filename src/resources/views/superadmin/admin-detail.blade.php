@extends('superadmin.layout', ['active' => 'admins'])

@section('title', 'Admin profile')

@section('content')
<div class="superadmin-heading"><div><a class="sa-back-link" href="{{ route('superadmin.admins.page') }}">← Admin &amp; Assignments</a><h1 id="admin-name">Admin profile</h1><p id="admin-meta">Đang tải thông tin tài khoản...</p></div><div class="superadmin-actions"><button class="sa-button secondary" id="admin-reset" type="button">Reset password</button><button class="sa-button danger" id="admin-status" type="button">Update status</button></div></div>
<div id="notice" class="sa-notice" role="status"></div>
<div class="sa-split"><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Account &amp; role</h2><p>Thông tin định danh và quyền truy cập.</p></div></div><div id="admin-summary" class="sa-detail-list"></div></section><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Room assignment</h2><p>Các Room tài khoản này có thể vận hành.</p></div></div><div id="admin-rooms" class="sa-health-list"></div></section></div>
@endsection

@push('scripts')
<script>
const adminId = @json(request()->route('admin'));
const adminNotice = (message, type = 'success') => { const n = document.querySelector('#notice'); n.textContent = message; n.className = `sa-notice ${type} is-visible`; };
async function loadAdmin() {
    const { data: admin } = await dfApi(`/superadmin/admins/${adminId}`);
    document.querySelector('#admin-name').textContent = admin.name;
    document.querySelector('#admin-meta').textContent = `${admin.email} · ${admin.role} · ${admin.status}`;
    document.querySelector('#admin-summary').innerHTML = `<div class="sa-detail-row"><span>Name</span><strong>${escapeHtml(admin.name)}</strong></div><div class="sa-detail-row"><span>Email</span><strong>${escapeHtml(admin.email)}</strong></div><div class="sa-detail-row"><span>Role</span><strong>${escapeHtml(admin.role)}</strong></div><div class="sa-detail-row"><span>Status</span><strong>${statusPill(admin.status)}</strong></div>`;
    document.querySelector('#admin-rooms').innerHTML = (admin.rooms || []).map(room => `<div class="sa-health-row"><div><strong>${escapeHtml(room.name)}</strong><small>${escapeHtml(room.slug)}</small></div><span class="status-pill status-active">Assigned</span></div>`).join('') || '<div class="sa-empty">Chưa assign Room.</div>';
    const statusButton = document.querySelector('#admin-status');
    statusButton.textContent = admin.status === 'active' ? 'Block account' : 'Unblock account';
    statusButton.onclick = () => updateAdminStatus(admin.status === 'active' ? 'blocked' : 'active');
    document.querySelector('#admin-reset').onclick = resetPassword;
}
async function updateAdminStatus(status) { try { await dfApi(`/superadmin/admins/${adminId}/status`, { method: 'PATCH', body: { status } }); adminNotice('Đã cập nhật trạng thái Admin.'); loadAdmin(); } catch (error) { adminNotice(error.message, 'error'); } }
async function resetPassword() { const password = prompt('Mật khẩu mới, tối thiểu 8 ký tự'); if (!password) return; try { await dfApi(`/superadmin/admins/${adminId}/reset-password`, { method: 'POST', body: { password } }); adminNotice('Đã reset mật khẩu.'); } catch (error) { adminNotice(error.message, 'error'); } }
loadAdmin().catch(error => adminNotice(error.message, 'error'));
</script>
@endpush
