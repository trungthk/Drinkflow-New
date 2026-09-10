@extends('superadmin.layout', ['active' => 'rooms'])

@section('title', 'Room profile')

@section('content')
<div class="superadmin-heading"><div><a class="sa-back-link" href="{{ route('superadmin.rooms.page') }}">← Room Management</a><h1 id="room-name">Room profile</h1><p id="room-meta">Đang tải thông tin Room...</p></div><div class="superadmin-actions"><button class="sa-button" id="room-status-action" type="button">Update status</button></div></div>
<div id="notice" class="sa-notice" role="status"></div>
<section class="sa-grid kpis"><article class="sa-card sa-kpi"><span class="label">Members</span><strong id="room-members" class="value">—</strong><span class="hint">Tổng thành viên</span></article><article class="sa-card sa-kpi"><span class="label">Active members</span><strong id="room-active-members" class="value">—</strong><span class="hint">Đang hoạt động</span></article><article class="sa-card sa-kpi"><span class="label">Campaigns</span><strong id="room-campaigns" class="value">—</strong><span class="hint">Campaign trong Room</span></article><article class="sa-card sa-kpi"><span class="label">Admins</span><strong id="room-admins" class="value">—</strong><span class="hint">Được phân quyền</span></article></section>
<div class="sa-split"><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Assigned admins</h2><p>Admin có quyền vận hành Room này.</p></div></div><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Admin</th><th>Role</th><th>Status</th></tr></thead><tbody id="room-admin-list"></tbody></table></div></section><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Recent campaigns</h2><p>Campaign mới nhất trong Room.</p></div></div><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Campaign</th><th>Status</th><th>Orders</th></tr></thead><tbody id="room-campaign-list"></tbody></table></div></section></div>
@endsection

@push('scripts')
<script>
const roomId = @json(request()->route('room'));
const roomNotice = (message, type = 'success') => { const n = document.querySelector('#notice'); n.textContent = message; n.className = `sa-notice ${type} is-visible`; };
async function loadRoom() {
    const { data: room } = await dfApi(`/superadmin/rooms/${roomId}`);
    document.querySelector('#room-name').textContent = room.name;
    document.querySelector('#room-meta').textContent = `${room.slug} · ${statusPill(room.status).replace(/<[^>]+>/g, '')}`;
    document.querySelector('#room-members').textContent = room.room_users_count ?? 0;
    document.querySelector('#room-active-members').textContent = (room.room_users || []).filter(user => user.status === 'active').length;
    document.querySelector('#room-campaigns').textContent = room.campaigns_count ?? (room.campaigns || []).length;
    document.querySelector('#room-admins').textContent = room.admins_count ?? (room.admins || []).length;
    document.querySelector('#room-admin-list').innerHTML = (room.admins || []).map(admin => `<tr><td><strong>${escapeHtml(admin.name)}</strong><br><small>${escapeHtml(admin.email)}</small></td><td>${escapeHtml(admin.role)}</td><td>${statusPill(admin.status)}</td></tr>`).join('') || '<tr><td colspan="3" class="sa-empty">Chưa có Admin được assign.</td></tr>';
    document.querySelector('#room-campaign-list').innerHTML = (room.campaigns || []).slice(0, 8).map(campaign => `<tr><td><strong>${escapeHtml(campaign.name)}</strong></td><td>${statusPill(campaign.status)}</td><td>${campaign.orders_count ?? '—'}</td></tr>`).join('') || '<tr><td colspan="3" class="sa-empty">Chưa có campaign.</td></tr>';
    document.querySelector('#room-status-action').textContent = room.status === 'active' ? 'Disable room' : 'Enable room';
    document.querySelector('#room-status-action').onclick = () => updateRoomStatus(room.status === 'active' ? 'disabled' : 'active');
}
async function updateRoomStatus(status) { try { await dfApi(`/superadmin/rooms/${roomId}/status`, { method: 'PATCH', body: { status } }); roomNotice('Đã cập nhật trạng thái Room.'); loadRoom(); } catch (error) { roomNotice(error.message, 'error'); } }
loadRoom().catch(error => roomNotice(error.message, 'error'));
</script>
@endpush
