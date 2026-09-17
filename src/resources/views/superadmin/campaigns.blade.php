@extends('superadmin.layout', ['title' => 'Global Campaigns', 'active' => 'campaigns'])
@section('content')
<div class="superadmin-heading"><div><p class="superadmin-eyebrow">Platform Data</p><h1>Global Campaigns</h1><p>Campaign, Room, orders và trạng thái toàn hệ thống.</p></div></div>
<div id="notice" class="sa-notice"></div>
<section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Campaign registry</h2><p>{{ $campaigns->total() }} campaign</p></div><form method="GET" class="superadmin-actions"><input name="q" value="{{ $filters['search'] ?? '' }}" class="sa-input" placeholder="Search campaign"><select name="status" class="sa-input"><option value="">All status</option>@foreach(['active','scheduled','closed','cancelled'] as $value)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $value }}</option>@endforeach</select><button class="sa-button secondary" type="submit">Filter</button></form></div>
<div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Campaign</th><th>Room</th><th>Orders</th><th>Debts</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($campaigns as $campaign)
    @php
        $status = $campaign->status instanceof BackedEnum ? $campaign->status->value : (string) $campaign->status;
    @endphp
    <tr><td><strong>{{ $campaign->name }}</strong><br><small>{{ $campaign->restaurant }}</small></td><td>{{ $campaign->room?->name }}</td><td>{{ $campaign->orders_count }}</td><td>{{ $campaign->debts_count }}</td><td>{{ $status }}</td><td>@if(!in_array($status, ['closed','cancelled'], true))<button class="sa-button secondary" onclick="forceCampaign({{ $campaign->id }}, 'force-close')">Force close</button><button class="sa-button danger" onclick="forceCampaign({{ $campaign->id }}, 'force-cancel')">Cancel</button>@else—@endif</td></tr>
@empty
    <tr><td colspan="6" class="sa-empty">Chưa có campaign.</td></tr>
@endforelse
</tbody></table></div><div class="mt-4">{{ $campaigns->links() }}</div></section>
@endsection
@push('scripts')<script>async function forceCampaign(id,action){if(!confirm('Xác nhận thao tác trên campaign này?'))return;try{await dfApi(`/superadmin/campaigns/${id}/${action}`,{method:'POST'});window.location.reload();}catch(e){alert(e.message);}}</script>@endpush
