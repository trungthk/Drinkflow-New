@extends('superadmin.layout', ['title' => 'Room Management', 'active' => 'rooms'])
@section('content')
<div class="superadmin-heading"><div><p class="superadmin-eyebrow">Core System</p><h1>Room Management</h1><p>Quản lý không gian phòng ban, trạng thái và mức độ sử dụng trên toàn hệ thống.</p></div><button class="sa-button" onclick="createRoom()"><span class="material-symbols-outlined">add</span>Tạo Room</button></div><div id="notice" class="sa-notice"></div>
<section class="sa-card sa-section"><div class="sa-section-header"><div><h2>All rooms</h2><p id="room-count">Đang tải dữ liệu...</p></div><input id="room-search" class="sa-input" placeholder="Tìm theo tên hoặc slug" oninput="loadRooms()"></div><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Room</th><th>Status</th><th>Members</th><th>Campaigns</th><th>Actions</th></tr></thead><tbody id="rooms-table"></tbody></table></div></section>
@endsection
@push('scripts')
<script>
const roomNotice=(message,type='success')=>{const n=document.querySelector('#notice');n.textContent=message;n.className=`sa-notice ${type} is-visible`;};
async function loadRooms(){const q=document.querySelector('#room-search').value;const {data}=await dfApi('{{ route('superadmin.rooms.index') }}'+(q?'?q='+encodeURIComponent(q):''));document.querySelector('#room-count').textContent=`${data.total} room`;document.querySelector('#rooms-table').innerHTML=data.data.length?data.data.map(r=>`<tr><td><strong>${escapeHtml(r.name)}</strong><br><small>${escapeHtml(r.slug)}</small></td><td>${statusPill(r.status)}</td><td>${r.room_users_count} <small>(${r.active_room_users_count} active)</small></td><td>${r.campaigns_count}</td><td><div class="superadmin-actions"><a class="sa-button secondary" href="/superadmin/rooms/${r.id}/page">Detail</a>${r.status==='active'?`<button class="sa-button danger" onclick="setRoomStatus(${r.id},'disabled')">Disable</button>`:`<button class="sa-button" onclick="setRoomStatus(${r.id},'active')">Enable</button>`}</div></td></tr>`).join(''):'<tr><td colspan="5" class="sa-empty">Chưa có Room phù hợp.</td></tr>';}
async function setRoomStatus(id,status){try{await dfApi(`/superadmin/rooms/${id}/status`,{method:'PATCH',body:{status}});roomNotice('Đã cập nhật trạng thái Room.');loadRooms();}catch(e){roomNotice(e.message,'error');}}
async function createRoom(){const name=prompt('Tên Room');if(!name)return;const slug=prompt('Slug URL',name.toLowerCase().replace(/\s+/g,'-'));if(!slug)return;try{await dfApi('{{ route('superadmin.rooms.store') }}',{method:'POST',body:{name,slug}});roomNotice('Đã tạo Room.');loadRooms();}catch(e){roomNotice(e.message,'error');}}
loadRooms();
</script>
@endpush
