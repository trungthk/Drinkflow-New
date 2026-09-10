@extends('superadmin.layout', ['title' => 'Global Audit Trail', 'active' => 'audit'])
@section('content')
<div class="superadmin-heading"><div><p class="superadmin-eyebrow">Platform Data</p><h1>Global Audit Trail</h1><p>Toàn bộ thay đổi nhạy cảm của hệ thống với actor, target, Room, before và after.</p></div></div><section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Audit events</h2><p id="audit-count">Đang tải dữ liệu...</p></div><input id="audit-event" class="sa-input" placeholder="Lọc theo event" oninput="loadAudit()"></div><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Time</th><th>Actor</th><th>Event</th><th>Target</th><th>Room</th></tr></thead><tbody id="audit-table"></tbody></table></div></section>
@endsection
@push('scripts')
<script>
async function loadAudit(){const event=document.querySelector('#audit-event').value;const {data}=await dfApi('{{ route('superadmin.audit-logs.index') }}'+(event?'?event='+encodeURIComponent(event):''));document.querySelector('#audit-count').textContent=`${data.total} events`;document.querySelector('#audit-table').innerHTML=data.data.length?data.data.map(a=>`<tr><td>${escapeHtml(a.created_at)}</td><td>${escapeHtml(a.actor_type)} #${a.actor_id||'system'}</td><td><strong>${escapeHtml(a.event)}</strong></td><td>${escapeHtml(a.target_type)} #${a.target_id||'—'}</td><td>${escapeHtml(a.room?.name||'Global')}</td></tr>`).join(''):'<tr><td colspan="5" class="sa-empty">Chưa có audit event.</td></tr>';}
loadAudit();
</script>
@endpush
