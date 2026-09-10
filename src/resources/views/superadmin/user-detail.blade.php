@extends('superadmin.layout', ['active' => 'users'])

@section('title', 'Global user profile')

@section('content')
<div class="superadmin-heading"><div><a class="sa-back-link" href="{{ route('superadmin.global-users.page') }}">← Global Users</a><h1 id="user-name">Global user profile</h1><p id="user-meta">Đang tải thông tin user...</p></div><button class="sa-button danger" id="user-status" type="button">Update status</button></div>
<div id="notice" class="sa-notice" role="status"></div>
<div class="sa-split"><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Identity metadata</h2><p>Thông tin liên kết OAuth được mask an toàn.</p></div></div><div id="identity-list" class="sa-detail-list"></div></section><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Room memberships</h2><p>Membership và thiết bị tin cậy theo từng Room.</p></div></div><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Room</th><th>Status</th><th>Devices</th><th>Actions</th></tr></thead><tbody id="membership-list"></tbody></table></div></section></div>
@endsection

@push('scripts')
<script>
const globalUserId = @json(request()->route('globalUser'));
const userNotice = (message, type = 'success') => { const n = document.querySelector('#notice'); n.textContent = message; n.className = `sa-notice ${type} is-visible`; };
async function loadUser() {
    const { data: user } = await dfApi(`/superadmin/global-users/${globalUserId}`);
    document.querySelector('#user-name').textContent = user.name;
    document.querySelector('#user-meta').textContent = `${user.email} · ${user.status}`;
    document.querySelector('#identity-list').innerHTML = `<div class="sa-detail-row"><span>Name</span><strong>${escapeHtml(user.name)}</strong></div><div class="sa-detail-row"><span>Email</span><strong>${escapeHtml(user.email)}</strong></div><div class="sa-detail-row"><span>Status</span><strong>${statusPill(user.status)}</strong></div>${(user.oauth_identities || []).map(identity => `<div class="sa-detail-row"><span>${escapeHtml(identity.provider)}</span><strong>${escapeHtml(identity.provider_email || 'Linked identity')}<small>${escapeHtml(identity.last_login_at || 'No login metadata')}</small></strong></div>`).join('')}`;
    document.querySelector('#membership-list').innerHTML = (user.room_users || []).map(membership => `<tr><td><strong>${escapeHtml(membership.room?.name || 'Room')}</strong><br><small>${escapeHtml(membership.room?.slug || '')}</small></td><td>${statusPill(membership.status)}</td><td>${(membership.devices || []).length}</td><td><button class="sa-button danger" onclick="removeMembership(${membership.id})">Remove</button></td></tr>`).join('') || '<tr><td colspan="4" class="sa-empty">User chưa có membership.</td></tr>';
    const button = document.querySelector('#user-status');
    button.textContent = user.status === 'blocked' ? 'Unblock user' : 'Block user';
    button.onclick = () => updateUserStatus(user.status === 'blocked' ? 'active' : 'blocked');
}
async function updateUserStatus(status) { try { await dfApi(`/superadmin/global-users/${globalUserId}/status`, { method: 'PATCH', body: { status } }); userNotice('Đã cập nhật trạng thái user.'); loadUser(); } catch (error) { userNotice(error.message, 'error'); } }
async function removeMembership(roomUserId) { if (!confirm('Remove membership và revoke các device của user trong Room này?')) return; try { await dfApi(`/superadmin/global-users/${globalUserId}/memberships/${roomUserId}`, { method: 'DELETE' }); userNotice('Đã remove membership.'); loadUser(); } catch (error) { userNotice(error.message, 'error'); } }
loadUser().catch(error => userNotice(error.message, 'error'));
</script>
@endpush
