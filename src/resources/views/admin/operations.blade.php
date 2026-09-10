@extends('admin.layout')

@section('title', 'Quản lý Room')
@section('active', request('tab', 'campaigns'))

@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Quản trị Room / Workspace Operations</p>
            <h1>Quản lý {{ $room->name }}</h1>
            <p>Điều hành campaign, đơn gom realtime, công nợ, thành viên, tài khoản VietQR và nhật ký audit trong cùng một workspace.</p>
        </div>
        <a class="sa-button secondary" href="{{ route('admin.dashboard.page', $room) }}"><span class="material-symbols-outlined">space_dashboard</span>Tổng quan Room</a>
    </div>
    <div id="notice" class="sa-notice"></div>
    <div class="admin-scope-banner">
        <span class="material-symbols-outlined">admin_panel_settings</span>
        <div><strong>Room Scope Isolation</strong><small>Admin chỉ xem và cập nhật dữ liệu thuộc {{ $room->name }}.</small></div>
    </div>
    <div class="admin-tab-shell">
        <span class="admin-tab-label">Phân hệ</span>
        <nav class="admin-tab-nav">
            @foreach (['campaigns' => 'Chiến dịch & Menu', 'orders' => 'Đơn gom Realtime', 'debts' => 'Công nợ', 'users' => 'Thành viên Room', 'payments' => 'Tài khoản VietQR', 'settings' => 'Cấu hình Room', 'notifications' => 'Kênh thông báo', 'reports' => 'Báo cáo', 'audit' => 'Audit Log'] as $tab => $label)
                <button type="button" data-tab="{{ $tab }}" class="tab admin-tab">{{ $label }}</button>
            @endforeach
        </nav>
    </div>
    <section id="panel" class="sa-card sa-section"></section>
@endsection

@push('scripts')
    <script>
        const urls = {
            campaigns: @json(route('admin.campaigns.index', $room)), campaignStore: @json(route('admin.campaigns.store', $room)),
            orders: @json(route('admin.orders.index', $room)), debts: @json(route('admin.debts.index', $room)),
            users: @json(route('admin.room-users.index', $room)), payments: @json(route('admin.payment-accounts.index', $room)),
            paymentStore: @json(route('admin.payment-accounts.store', $room)), paymentBase: @json(url("admin/{$room->id}/payment-accounts")), settings: @json(route('admin.settings.show', $room)),
            reports: @json(route('admin.reports.index', $room)), notifications: @json(route('admin.notification-channels.index', $room)),
            notificationStore: @json(route('admin.notification-channels.store', $room)), audit: @json(route('admin.audit.index', $room))
        };
        const panel = document.querySelector('#panel');
        const notice = document.querySelector('#notice');
        const esc = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
        const money = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' ₫';
        const showNotice = (message, error = false) => { notice.textContent = message; notice.className = `mb-4 rounded-xl px-4 py-3 text-sm ${error ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`; };
        async function api(url, options = {}) {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) }, ...options });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).flat().join(' ') || `HTTP ${response.status}`);
            return body;
        }
        async function mutate(url, method = 'POST', body = null) {
            const result = await api(url, { method, ...(body ? { body: JSON.stringify(body) } : {}) });
            showNotice('Đã cập nhật thành công.');
            return result;
        }
        const header = (title, description) => `<div class="mb-5"><h2 class="text-xl font-bold">${title}</h2><p class="mt-1 text-sm text-slate-500">${description}</p></div>`;
        const actionButton = (label, action, color = 'slate') => `<button type="button" data-action="${action}" class="rounded-lg bg-${color}-600 px-3 py-1.5 text-xs font-semibold text-white">${label}</button>`;
        const tableEmpty = (columns, text) => `<tr><td class="p-4 text-slate-500" colspan="${columns}">${text}</td></tr>`;
        async function submitForm(event, task) { event.preventDefault(); try { await task(); } catch (error) { showNotice(error.message, true); } }

        const campaignStatus = status => {
            const labels = { draft: 'Bản nháp', scheduled: 'Đã lên lịch', active: 'Đang mở', closing: 'Sắp chốt', closed: 'Đã chốt', archived: 'Đã lưu trữ' };
            const tone = ['active'].includes(status) ? 'is-live' : ['closed', 'archived'].includes(status) ? 'is-done' : status === 'scheduled' ? 'is-scheduled' : 'is-draft';
            return `<span class="campaign-status ${tone}"><i></i>${labels[status] || esc(status)}</span>`;
        };
        const campaignDeadline = deadline => deadline ? new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }).format(new Date(deadline)) : 'Chưa đặt hạn';
        const campaignActions = campaign => `<div class="campaign-row-actions">${['draft', 'scheduled'].includes(campaign.status) ? `<button type="button" class="campaign-icon-action is-primary" title="Phát động" aria-label="Phát động ${esc(campaign.name)}" data-action="activate:${campaign.id}"><span class="material-symbols-outlined">rocket_launch</span></button>` : ''}${['active', 'closing'].includes(campaign.status) ? `<button type="button" class="campaign-icon-action" title="Chốt đơn" aria-label="Chốt đơn ${esc(campaign.name)}" data-action="close:${campaign.id}"><span class="material-symbols-outlined">lock</span></button>` : ''}<button type="button" class="campaign-icon-action" title="Nhân bản" aria-label="Nhân bản ${esc(campaign.name)}" data-action="duplicate:${campaign.id}"><span class="material-symbols-outlined">content_copy</span></button>${!['closed', 'archived'].includes(campaign.status) ? `<button type="button" class="campaign-icon-action is-danger" title="Lưu trữ" aria-label="Lưu trữ ${esc(campaign.name)}" data-action="archive:${campaign.id}"><span class="material-symbols-outlined">archive</span></button>` : ''}</div>`;
        function campaignMetrics(rows) {
            const active = rows.filter(row => ['active', 'closing'].includes(row.status));
            const today = new Date().toDateString();
            const createdToday = rows.filter(row => new Date(row.created_at).toDateString() === today).length;
            const orders = rows.reduce((sum, row) => sum + Number(row.orders_count || 0), 0);
            return [
                ['campaign', 'Chiến dịch đang mở', active.length, active.length ? 'Cần theo dõi deadline' : 'Chưa có chiến dịch mở', 'is-mint'],
                ['shopping_bag', 'Tổng đơn đã gom', orders, `${rows.length} chiến dịch trong Room`, 'is-sky'],
                ['calendar_month', 'Tạo trong hôm nay', createdToday, 'Theo dõi lịch phát động', 'is-amber'],
                ['schedule', 'Chờ phát động', rows.filter(row => ['draft', 'scheduled'].includes(row.status)).length, 'Bản nháp và đã lên lịch', 'is-ink']
            ];
        }
        function campaignList(rows) {
            return `<div class="campaign-table-wrap"><table class="campaign-table"><thead><tr><th>Chiến dịch</th><th>Quán / nhà hàng</th><th>Hạn chốt đơn</th><th class="has-number">Đơn gom</th><th>Trạng thái</th><th><span class="sr-only">Thao tác</span></th></tr></thead><tbody>${rows.map(c => `<tr data-campaign-row data-search="${esc(`${c.name} ${c.restaurant} ${c.status}`.toLowerCase())}" data-status="${esc(c.status)}"><td><div class="campaign-name-cell"><span class="campaign-avatar">${esc((c.name || 'C').trim().charAt(0).toUpperCase())}</span><div><strong>${esc(c.name)}</strong><small>#CAM-${esc(c.id)}</small></div></div></td><td><div class="campaign-restaurant"><span class="material-symbols-outlined">storefront</span><span>${esc(c.restaurant)}</span></div></td><td><span class="campaign-deadline"><span class="material-symbols-outlined">schedule</span>${campaignDeadline(c.deadline)}</span></td><td class="has-number"><strong>${esc(c.orders_count || 0)}</strong><small>đơn</small></td><td>${campaignStatus(c.status)}</td><td>${campaignActions(c)}</td></tr>`).join('') || `<tr><td colspan="6"><div class="campaign-empty"><span class="material-symbols-outlined">campaign</span><strong>Chưa có chiến dịch</strong><p>Tạo chiến dịch đầu tiên để bắt đầu gom đơn cho Room.</p></div></td></tr>`}</tbody></table></div>`;
        }
        function bindCampaignFilters() {
            const search = panel.querySelector('#campaign-search');
            const filters = panel.querySelectorAll('[data-campaign-filter]');
            const apply = () => {
                const term = search.value.trim().toLowerCase();
                const activeFilter = panel.querySelector('[data-campaign-filter].is-active')?.dataset.campaignFilter || 'all';
                panel.querySelectorAll('[data-campaign-row]').forEach(row => {
                    row.hidden = !row.dataset.search.includes(term) || (activeFilter !== 'all' && row.dataset.status !== activeFilter);
                });
            };
            search?.addEventListener('input', apply);
            filters.forEach(button => button.addEventListener('click', () => {
                filters.forEach(item => item.classList.remove('is-active'));
                button.classList.add('is-active');
                apply();
            }));
        }
        function bindCampaignForm() {
            const form = panel.querySelector('#campaign-form');
            const deadline = form?.querySelector('[name="deadline"]');
            const setDeadline = minutes => {
                const date = new Date(Date.now() + minutes * 60000);
                date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
                deadline.value = date.toISOString().slice(0, 16);
            };
            panel.querySelectorAll('[data-deadline-minutes]').forEach(button => button.addEventListener('click', () => setDeadline(Number(button.dataset.deadlineMinutes))));
            panel.querySelector('[data-campaign-view="list"]')?.addEventListener('click', () => campaigns());
            form.onsubmit = event => submitForm(event, async () => {
                const data = Object.fromEntries(new FormData(event.target));
                data.status = data.launch_now === '1' ? 'active' : 'draft';
                delete data.launch_now;
                Object.keys(data).forEach(key => { if (data[key] === '') delete data[key]; });
                await mutate(urls.campaignStore, 'POST', data);
                await campaigns();
            });
            setDeadline(60);
        }
        async function campaignCreate() {
            panel.innerHTML = `<section class="campaign-create"><div class="campaign-create-top"><button type="button" class="campaign-back" data-campaign-view="list"><span class="material-symbols-outlined">arrow_back</span><span>Danh sách chiến dịch</span></button><div><p class="campaign-kicker">Thiết lập chiến dịch</p><h2>Tạo chiến dịch gom đơn</h2><p>Chuẩn bị thông tin quán, hạn chốt và chính sách áp dụng trước khi phát động.</p></div></div><form id="campaign-form" class="campaign-create-grid"><div class="campaign-form-main"><section class="campaign-form-section"><div class="campaign-section-heading"><span class="campaign-section-icon is-mint"><span class="material-symbols-outlined">storefront</span></span><div><h3>Nguồn đơn &amp; nhận diện</h3><p>Thông tin hiển thị cho toàn bộ thành viên trong Room.</p></div></div><div class="campaign-field-grid"><label class="campaign-field is-full"><span>Tên chiến dịch</span><input name="name" required maxlength="160" placeholder="Ví dụ: Cà phê chiều thứ Sáu" autocomplete="off"></label><label class="campaign-field"><span>Quán / nhà hàng</span><input name="restaurant" required maxlength="160" placeholder="Ví dụ: Phê La - Tràng Tiền" autocomplete="off"></label><label class="campaign-field"><span>Nhà tài trợ <em>Không bắt buộc</em></span><input name="sponsor_name" maxlength="160" placeholder="Ví dụ: Team Engineering"></label><label class="campaign-field is-full"><span>Ghi chú cho thành viên <em>Không bắt buộc</em></span><textarea name="description" maxlength="5000" rows="3" placeholder="Nội dung ngắn sẽ xuất hiện cùng chiến dịch."></textarea></label></div></section><section class="campaign-form-section"><div class="campaign-section-heading"><span class="campaign-section-icon is-sky"><span class="material-symbols-outlined">schedule</span></span><div><h3>Thời gian &amp; ngân sách</h3><p>Đặt hạn chốt rõ ràng để Room đồng bộ tiến độ gom đơn.</p></div></div><div class="campaign-field-grid"><label class="campaign-field"><span>Hạn chốt đơn</span><input name="deadline" type="datetime-local" required></label><div class="campaign-field"><span>Chọn nhanh</span><div class="campaign-time-actions"><button type="button" data-deadline-minutes="30">+30 phút</button><button type="button" data-deadline-minutes="60">+1 giờ</button><button type="button" data-deadline-minutes="120">+2 giờ</button></div></div><label class="campaign-field"><span>Ngân sách tối đa <em>Không bắt buộc</em></span><div class="campaign-input-suffix"><input name="max_budget" type="number" min="0" placeholder="0"><span>VNĐ</span></div></label><label class="campaign-field"><span>Phí giao hàng <em>Không bắt buộc</em></span><div class="campaign-input-suffix"><input name="delivery_fee" type="number" min="0" placeholder="0"><span>VNĐ</span></div></label></div></section></div><aside class="campaign-rules"><div class="campaign-rules-head"><span class="campaign-section-icon is-ink"><span class="material-symbols-outlined">tune</span></span><div><h3>Phát động</h3><p>Kiểm tra trước khi công bố.</p></div></div><label class="campaign-launch-option"><input name="launch_now" type="checkbox" value="1" checked><span class="campaign-check"><span class="material-symbols-outlined">check</span></span><span><strong>Phát động ngay</strong><small>Thành viên có thể bắt đầu đặt đơn sau khi tạo.</small></span></label><div class="campaign-rule-list"><div><span class="material-symbols-outlined">group</span><span>Áp dụng cho thành viên Room hiện tại.</span></div><div><span class="material-symbols-outlined">notifications_active</span><span>Trạng thái chiến dịch sẽ đồng bộ realtime.</span></div><div><span class="material-symbols-outlined">verified_user</span><span>Chỉ admin Room có quyền phát động.</span></div></div><div class="campaign-create-actions"><button type="button" class="campaign-secondary" data-campaign-view="list">Hủy</button><button type="submit" class="campaign-submit"><span class="material-symbols-outlined">rocket_launch</span><span>Tạo chiến dịch</span></button></div></aside></form></section>`;
            bindCampaignForm();
        }
        async function campaigns() {
            const rows = (await api(urls.campaigns)).data.data || [];
            const metrics = campaignMetrics(rows);
            panel.innerHTML = `<section class="campaign-workspace"><div class="campaign-workspace-head"><div><p class="campaign-kicker">Điều hành Room</p><h2>Chiến dịch &amp; Menu</h2><p>Theo dõi tiến độ gom đơn, hạn chốt và trạng thái phát động trong một nơi.</p></div><button type="button" class="campaign-create-button" data-campaign-view="create"><span class="material-symbols-outlined">add</span><span>Tạo chiến dịch</span></button></div><div class="campaign-metrics">${metrics.map(([icon, label, value, hint, tone]) => `<article class="campaign-metric"><span class="campaign-metric-icon ${tone}"><span class="material-symbols-outlined">${icon}</span></span><span class="campaign-metric-label">${label}</span><strong>${esc(value)}</strong><small>${hint}</small></article>`).join('')}</div><section class="campaign-list-card"><div class="campaign-list-toolbar"><div><h3>Danh sách chiến dịch</h3><p>${rows.length} chiến dịch trong Room</p></div><div class="campaign-toolbar-controls"><label class="campaign-search"><span class="material-symbols-outlined">search</span><input id="campaign-search" type="search" placeholder="Tìm chiến dịch hoặc quán" aria-label="Tìm chiến dịch hoặc quán"></label><button type="button" class="campaign-filter is-active" data-campaign-filter="all">Tất cả</button><button type="button" class="campaign-filter" data-campaign-filter="active">Đang mở</button><button type="button" class="campaign-filter" data-campaign-filter="draft">Bản nháp</button></div></div>${campaignList(rows)}</section></section>`;
            panel.querySelector('[data-campaign-view="create"]')?.addEventListener('click', campaignCreate);
            bindCampaignFilters();
        }
        async function orders() {
            const rows = (await api(urls.orders)).data.data || [];
            panel.innerHTML = header('Orders', 'Theo dõi đơn theo Room và thực hiện cancel, delete, unlock.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">#</th><th class="p-3">User</th><th class="p-3">Campaign</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(order => `<tr class="border-b"><td class="p-3">${esc(order.id)}</td><td class="p-3">${esc(order.room_user?.global_user?.name || order.room_user?.display_name)}</td><td class="p-3">${esc(order.campaign?.name)}</td><td class="p-3">${money(order.final_amount)}</td><td class="p-3">${esc(order.status)}</td><td class="flex flex-wrap gap-2 p-3">${!['completed', 'cancelled'].includes(order.status) ? actionButton('Cancel', `order-cancel:${order.id}`, 'amber') : ''}${order.status !== 'completed' ? actionButton('Delete', `order-delete:${order.id}`, 'red') : ''}${['submitted', 'confirmed', 'ordering'].includes(order.status) ? actionButton('Unlock', `order-unlock:${order.id}`) : ''}</td></tr>`).join('') || tableEmpty(6, 'Chưa có order.')}</tbody></table></div>`;
        }
        async function debts() {
            const rows = (await api(urls.debts)).data.data || [];
            panel.innerHTML = header('Debts', 'Xem số tiền phải thu và cập nhật trạng thái thanh toán.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">User</th><th class="p-3">Campaign</th><th class="p-3">Original</th><th class="p-3">Remaining</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(debt => `<tr class="border-b"><td class="p-3">${esc(debt.room_user?.global_user?.name || debt.room_user?.display_name)}</td><td class="p-3">${esc(debt.campaign?.name)}</td><td class="p-3">${money(debt.original_amount)}</td><td class="p-3 font-semibold">${money(debt.remaining_amount)}</td><td class="p-3">${esc(debt.status)}</td><td class="p-3">${debt.status !== 'paid' ? actionButton('Mark paid', `debt-paid:${debt.id}`, 'emerald') : actionButton('Mark unpaid', `debt-unpaid:${debt.id}`)}</td></tr>`).join('') || tableEmpty(6, 'Chưa có debt.')}</tbody></table></div>`;
        }
        const memberName = user => user.global_user?.name || user.display_name || 'Thành viên Room';
        const memberInitials = user => memberName(user).trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase();
        const memberStatus = status => {
            const labels = { active: 'Hoạt động', blocked: 'Đã chặn', pending: 'Chờ xác nhận', inactive: 'Không hoạt động' };
            const tone = status === 'active' ? 'is-active' : status === 'blocked' ? 'is-blocked' : 'is-pending';
            return `<span class="member-status ${tone}"><i></i>${labels[status] || esc(status)}</span>`;
        };
        const memberActivity = date => {
            if (!date) return 'Chưa ghi nhận';
            const value = new Date(date);
            if (Number.isNaN(value.getTime())) return 'Chưa ghi nhận';
            return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(value);
        };
        const memberActions = user => `<div class="member-row-actions"><button type="button" class="member-icon-action ${user.status === 'blocked' ? 'is-restore' : 'is-danger'}" title="${user.status === 'blocked' ? 'Khôi phục quyền truy cập' : 'Chặn thành viên'}" aria-label="${user.status === 'blocked' ? 'Khôi phục quyền truy cập cho' : 'Chặn'} ${esc(memberName(user))}" data-action="user-${user.status === 'blocked' ? 'active' : 'blocked'}:${user.id}"><span class="material-symbols-outlined">${user.status === 'blocked' ? 'lock_open' : 'block'}</span></button></div>`;
        function memberMetrics(rows) {
            const active = rows.filter(user => user.status === 'active');
            const devices = rows.reduce((total, user) => total + Number(user.devices_count || 0), 0);
            const orders = rows.reduce((total, user) => total + Number(user.orders_count || 0), 0);
            const owing = rows.filter(user => Number(user.debts_count || 0) > 0).length;
            return [
                ['group', 'Tổng thành viên', rows.length, `${active.length} đang hoạt động`, 'is-ink'],
                ['verified_user', 'Thiết bị tin cậy', devices, 'Đã xác thực trong Room', 'is-mint'],
                ['shopping_bag', 'Đơn đã tham gia', orders, 'Tổng lượt đặt của thành viên', 'is-sky'],
                ['account_balance_wallet', 'Cần đối soát', owing, 'Thành viên có công nợ', 'is-amber']
            ];
        }
        function memberTable(rows) {
            return `<div class="member-table-wrap"><table class="member-table"><thead><tr><th>Thành viên</th><th>Mã Room User</th><th class="has-number">Đơn đã đặt</th><th class="has-number">Thiết bị</th><th>Công nợ</th><th>Hoạt động gần nhất</th><th>Trạng thái</th><th><span class="sr-only">Thao tác</span></th></tr></thead><tbody>${rows.map(user => `<tr data-member-row data-search="${esc(`${memberName(user)} ${user.global_user?.email || ''} ${user.user_code || ''}`.toLowerCase())}" data-status="${esc(user.status)}"><td><div class="member-name-cell"><span class="member-avatar">${esc(memberInitials(user))}</span><div><strong>${esc(memberName(user))}</strong><small>${esc(user.global_user?.email || 'Chưa đồng bộ email')}</small></div></div></td><td><span class="member-code">${esc(user.user_code || `#ROOM-${user.id}`)}</span></td><td class="has-number"><strong>${esc(user.orders_count || 0)}</strong><small>đơn</small></td><td class="has-number"><span class="member-device-count"><span class="material-symbols-outlined">devices</span>${esc(user.devices_count || 0)}</span></td><td>${Number(user.debts_count || 0) ? `<span class="member-debt is-owing">${esc(user.debts_count)} cần thu</span>` : '<span class="member-debt">Đã đối soát</span>'}</td><td><span class="member-activity"><span class="material-symbols-outlined">schedule</span>${memberActivity(user.last_active_at || user.updated_at)}</span></td><td>${memberStatus(user.status)}</td><td>${memberActions(user)}</td></tr>`).join('') || `<tr><td colspan="8"><div class="member-empty"><span class="material-symbols-outlined">group_off</span><strong>Chưa có thành viên</strong><p>Thành viên được hiển thị khi đã tham gia Room.</p></div></td></tr>`}</tbody></table></div>`;
        }
        function bindMemberFilters() {
            const search = panel.querySelector('#member-search');
            const filters = panel.querySelectorAll('[data-member-filter]');
            const apply = () => {
                const term = search.value.trim().toLowerCase();
                const activeFilter = panel.querySelector('[data-member-filter].is-active')?.dataset.memberFilter || 'all';
                panel.querySelectorAll('[data-member-row]').forEach(row => {
                    row.hidden = !row.dataset.search.includes(term) || (activeFilter !== 'all' && row.dataset.status !== activeFilter);
                });
            };
            search?.addEventListener('input', apply);
            filters.forEach(button => button.addEventListener('click', () => {
                filters.forEach(item => item.classList.remove('is-active'));
                button.classList.add('is-active');
                apply();
            }));
        }
        async function users() {
            const rows = (await api(urls.users)).data.data || [];
            const metrics = memberMetrics(rows);
            panel.innerHTML = `<section class="member-workspace"><div class="member-workspace-head"><div><p class="member-kicker">Quản trị Room</p><h2>Thành viên Room</h2><p>Quản lý quyền truy cập, thiết bị tin cậy và lịch sử tham gia gom đơn của từng thành viên.</p></div></div><div class="member-metrics">${metrics.map(([icon, label, value, hint, tone]) => `<article class="member-metric"><span class="member-metric-icon ${tone}"><span class="material-symbols-outlined">${icon}</span></span><span class="member-metric-label">${label}</span><strong>${esc(value)}</strong><small>${hint}</small></article>`).join('')}</div><section class="member-list-card"><div class="member-list-toolbar"><div><h3>Danh sách thành viên</h3><p>${rows.length} thành viên trong Room</p></div><div class="member-toolbar-controls"><label class="member-search"><span class="material-symbols-outlined">search</span><input id="member-search" type="search" placeholder="Tìm tên, email hoặc mã Room" aria-label="Tìm thành viên"></label><button type="button" class="member-filter is-active" data-member-filter="all">Tất cả</button><button type="button" class="member-filter" data-member-filter="active">Hoạt động</button><button type="button" class="member-filter" data-member-filter="blocked">Đã chặn</button></div></div>${memberTable(rows)}</section></section>`;
            bindMemberFilters();
        }
        async function payments() {
            const rows = (await api(urls.payments)).data.data || [];
            const active = rows.filter(account => account.status === 'active');
            const defaultAccount = active.find(account => account.is_default);
            const accountCard = account => `<article class="rounded-xl border border-[#e2e8f0] bg-white p-4 shadow-sm"><div class="flex items-start justify-between gap-3"><div class="flex items-center gap-3"><span class="material-symbols-outlined grid h-10 w-10 place-items-center rounded-lg bg-[#eff4ff] text-[#0b1c30]">account_balance</span><div><strong class="block text-sm">${esc(account.bank_name)}</strong><small class="text-[10px] uppercase tracking-wider text-slate-500">${esc(account.bank_code)}</small></div></div>${account.is_default ? '<span class="status-pill status-active">Mặc định</span>' : `<span class="status-pill ${account.status === 'active' ? 'status-pending' : 'status-blocked'}">${account.status === 'active' ? 'Dự phòng' : 'Tạm ngưng'}</span>`}</div><div class="mt-4 rounded-lg bg-[#eff4ff] p-3"><small class="block text-[10px] uppercase text-slate-500">Số tài khoản</small><strong class="mt-1 block tracking-wide">${esc(account.account_number)}</strong><small class="mt-2 block text-slate-500">${esc(account.account_name)}</small></div><div class="mt-4 flex flex-wrap gap-2">${!account.is_default && account.status === 'active' ? `<button data-action="payment-default:${account.id}" class="rounded-lg bg-[#0b1c30] px-3 py-2 text-[11px] font-bold text-white">Đặt làm mặc định</button>` : ''}${account.status === 'active' ? `<button data-action="payment-disable:${account.id}" class="rounded-lg bg-[#fee2e2] px-3 py-2 text-[11px] font-bold text-red-700">Tạm ngưng</button>` : `<button data-action="payment-enable:${account.id}" class="rounded-lg bg-[#d1fae5] px-3 py-2 text-[11px] font-bold text-[#047857]">Kích hoạt lại</button>`}</div></article>`;
            panel.innerHTML = `<section class="space-y-5"><div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end"><div><p class="superadmin-eyebrow">Quản trị Room / Tài khoản nhận tiền</p><h2 class="text-2xl font-bold tracking-tight">Tài khoản nhận tiền &amp; Cổng thanh toán VietQR</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Cấu hình tài khoản ngân hàng thụ hưởng, tài khoản mặc định của Host và cơ chế đối soát VietQR theo Room.</p></div><button type="button" data-payment-create class="sa-button"><span class="material-symbols-outlined">add_card</span>Thêm tài khoản ngân hàng</button></div><div class="flex items-center gap-2 rounded-xl bg-[#eff4ff] px-4 py-3 text-[11px] text-slate-600"><span class="status-dot"></span><span>Webhook Napas 24/7 đang đồng bộ trực tiếp</span><button type="button" data-payment-check class="ml-auto font-bold text-[#006c49]">Kiểm tra kết nối</button></div><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article class="sa-card p-4"><span class="text-[10px] font-bold uppercase text-slate-500">Tài khoản hoạt động</span><strong class="mt-3 block text-3xl">${active.length}</strong><small class="mt-2 block text-slate-500">${defaultAccount ? '1 tài khoản mặc định' : 'Chưa chọn mặc định'}</small></article><article class="sa-card p-4"><span class="text-[10px] font-bold uppercase text-slate-500">Định tuyến VietQR</span><strong class="mt-3 block text-xl text-[#006c49]">Napas 24/7</strong><small class="mt-2 block text-[#006c49]">Sẵn sàng đối soát</small></article><article class="sa-card p-4"><span class="text-[10px] font-bold uppercase text-slate-500">Tài khoản dự phòng</span><strong class="mt-3 block text-3xl">${Math.max(0, active.length - (defaultAccount ? 1 : 0))}</strong><small class="mt-2 block text-slate-500">Có thể chuyển định tuyến</small></article><article class="sa-card p-4"><span class="text-[10px] font-bold uppercase text-slate-500">Cú pháp VietQR</span><strong class="mt-3 block text-sm">DF ROOM [USER_ID]</strong><small class="mt-2 block text-slate-500">Nội dung chuyển khoản chuẩn</small></article></div><section><div class="mb-3 flex items-center justify-between"><div><h3 class="text-base font-bold">Danh sách tài khoản thụ hưởng</h3><p class="mt-1 text-[11px] text-slate-500">Dữ liệu số tài khoản được che theo chính sách bảo mật Room.</p></div><span class="text-[11px] text-slate-500">${rows.length} tài khoản</span></div><div class="grid gap-4 lg:grid-cols-2">${rows.map(accountCard).join('') || '<div class="sa-empty rounded-xl border border-dashed border-slate-200 bg-slate-50">Chưa có tài khoản nhận tiền.</div>'}</div></section><form id="payment-form" class="hidden rounded-xl border border-[#dce9ff] bg-[#eff4ff] p-5"><div class="mb-4 flex items-center justify-between"><div><h3 class="text-base font-bold">Thêm tài khoản ngân hàng mới</h3><p class="mt-1 text-[11px] text-slate-500">Tài khoản mới có thể được chọn làm định tuyến mặc định ngay sau khi lưu.</p></div><button type="button" data-payment-close class="text-slate-500">Đóng</button></div><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"><label class="text-[11px] font-bold">Mã ngân hàng<input name="bank_code" required placeholder="Ví dụ: MBB" class="mt-1 w-full rounded-lg border border-slate-200 bg-white p-2.5 font-normal"></label><label class="text-[11px] font-bold">Tên ngân hàng<input name="bank_name" required placeholder="Ví dụ: MB Bank" class="mt-1 w-full rounded-lg border border-slate-200 bg-white p-2.5 font-normal"></label><label class="text-[11px] font-bold">Số tài khoản<input name="account_number" required inputmode="numeric" placeholder="Số tài khoản" class="mt-1 w-full rounded-lg border border-slate-200 bg-white p-2.5 font-normal"></label><label class="text-[11px] font-bold">Chủ tài khoản<input name="account_name" required placeholder="Tên chủ tài khoản" class="mt-1 w-full rounded-lg border border-slate-200 bg-white p-2.5 font-normal"></label></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><label class="inline-flex items-center gap-2 text-[11px] font-semibold"><input name="is_default" value="1" type="checkbox" class="accent-[#006c49]">Đặt làm tài khoản mặc định</label><input name="status" value="active" type="hidden"><button type="submit" class="sa-button"><span class="material-symbols-outlined">save</span>Lưu tài khoản</button></div></form></section>`;
            panel.querySelector('[data-payment-create]')?.addEventListener('click', () => panel.querySelector('#payment-form').classList.remove('hidden'));
            panel.querySelector('[data-payment-close]')?.addEventListener('click', () => panel.querySelector('#payment-form').classList.add('hidden'));
            panel.querySelector('[data-payment-check]')?.addEventListener('click', () => showNotice('Webhook Napas phản hồi 200 OK.'));
            panel.querySelector('#payment-form').onsubmit = event => submitForm(event, async () => { const data = Object.fromEntries(new FormData(event.target)); data.is_default = event.target.is_default.checked; await mutate(urls.paymentStore, 'POST', data); await payments(); });
        }
        async function settings() {
            const data = (await api(urls.settings)).data || {};
            panel.innerHTML = header('Room Settings', 'Cấu hình tên, mô tả, ngôn ngữ và timezone cho Room.') + `<form id="settings-form" class="grid max-w-2xl gap-4"><label class="text-sm font-semibold">Name<input name="name" value="${esc(data.name)}" required class="mt-1 w-full rounded-lg border p-2 font-normal"></label><label class="text-sm font-semibold">Description<textarea name="description" class="mt-1 w-full rounded-lg border p-2 font-normal">${esc(data.description)}</textarea></label><label class="text-sm font-semibold">Language<select name="language" class="mt-1 w-full rounded-lg border p-2 font-normal"><option ${data.language === 'vi' ? 'selected' : ''}>vi</option><option ${data.language === 'en' ? 'selected' : ''}>en</option><option ${data.language === 'ja' ? 'selected' : ''}>ja</option></select></label><label class="text-sm font-semibold">Timezone<input name="timezone" value="${esc(data.timezone || 'Asia/Ho_Chi_Minh')}" class="mt-1 w-full rounded-lg border p-2 font-normal"></label><button type="submit" class="w-fit rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Save settings</button></form>`;
            document.querySelector('#settings-form').onsubmit = event => submitForm(event, async () => { await mutate(urls.settings, 'PATCH', Object.fromEntries(new FormData(event.target))); });
        }
        async function notifications() {
            const rows = (await api(urls.notifications)).data || [];
            panel.innerHTML = header('Notification channels', 'Credential chỉ lưu encrypted và giao diện chỉ hiển thị trạng thái configured.') + `<form id="notification-form" class="mb-6 grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-4"><select name="type" class="rounded-lg border p-2 text-sm"><option>slack</option><option>telegram</option><option>chatwork</option><option>webhook</option></select><input name="name" required placeholder="Tên channel" class="rounded-lg border p-2 text-sm"><input name="config_json" required placeholder='{"webhook":"https://..."}' class="rounded-lg border p-2 text-sm"><button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Configure</button></form><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Type</th><th class="p-3">Name</th><th class="p-3">Status</th><th class="p-3">Credential</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(channel => `<tr class="border-b"><td class="p-3">${esc(channel.type)}</td><td class="p-3">${esc(channel.name)}</td><td class="p-3">${esc(channel.status)}</td><td class="p-3">${channel.configured ? 'Configured · ••••••••••' : 'Not configured'}</td><td class="flex gap-2 p-3">${channel.configured ? actionButton('Test', `notify-test:${channel.id}`, 'blue') : ''}${channel.status !== 'disabled' ? actionButton('Disable', `notify-disable:${channel.id}`) : ''}</td></tr>`).join('') || tableEmpty(5, 'Chưa có channel.')}</tbody></table></div>`;
            document.querySelector('#notification-form').onsubmit = event => submitForm(event, async () => { const form = Object.fromEntries(new FormData(event.target)); await mutate(urls.notificationStore, 'POST', { type: form.type, name: form.name, status: 'enabled', config: JSON.parse(form.config_json) }); event.target.reset(); await notifications(); });
        }
        async function reports() {
            const data = (await api(urls.reports + '?period=month')).data || {};
            panel.innerHTML = header('Reports', 'Thống kê theo tháng hiện tại.') + `<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">${[['Campaigns', data.campaign_count], ['Orders', data.order_count], ['Spending', money(data.spending)], ['Sponsor', money(data.sponsor_amount)], ['Debt', money(data.debt)], ['Participants', data.user_participation]].map(([label, value]) => `<article class="rounded-xl bg-slate-50 p-4"><p class="text-sm text-slate-500">${label}</p><p class="mt-1 text-xl font-bold">${esc(value)}</p></article>`).join('')}</div>`;
        }
        async function audit() {
            const rows = (await api(urls.audit)).data.data || [];
            panel.innerHTML = header('Room audit', 'Chỉ hiển thị audit log thuộc Room đang chọn.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Time</th><th class="p-3">Event</th><th class="p-3">Target</th><th class="p-3">Admin</th></tr></thead><tbody>${rows.map(log => `<tr class="border-b"><td class="p-3">${esc(log.created_at)}</td><td class="p-3 font-semibold">${esc(log.event)}</td><td class="p-3">${esc(log.target_type)} #${esc(log.target_id)}</td><td class="p-3">${esc(log.admin_id)}</td></tr>`).join('') || tableEmpty(4, 'Chưa có audit.')}</tbody></table></div>`;
        }
        async function load(tab) {
            document.querySelectorAll('.tab').forEach(button => button.className = `tab admin-tab ${button.dataset.tab === tab ? 'is-active' : ''}`);
            try { await ({ campaigns, orders, debts, users, payments, settings, notifications, reports, audit }[tab])(); } catch (error) { showNotice(error.message, true); panel.innerHTML = '<p class="text-red-600">Không tải được dữ liệu.</p>'; }
        }
        document.querySelectorAll('.tab').forEach(button => button.addEventListener('click', () => load(button.dataset.tab)));
        panel.addEventListener('click', async event => {
            const action = event.target.closest('[data-action]')?.dataset.action; if (!action) return;
            const [type, id] = action.split(':');
            try {
                if (type === 'activate') await mutate(`${urls.campaigns}/${id}/activate`);
                if (type === 'close') await mutate(`${urls.campaigns}/${id}/close`);
                if (type === 'archive') await mutate(`${urls.campaigns}/${id}/archive`);
                if (type === 'duplicate') await mutate(`${urls.campaigns}/${id}/duplicate`);
                if (type === 'order-cancel') await mutate(`${urls.orders}/${id}/cancel`);
                if (type === 'order-delete') await mutate(`${urls.orders}/${id}`, 'DELETE');
                if (type === 'order-unlock') await mutate(`${urls.orders}/${id}/unlock`);
                if (type === 'payment-default') await mutate(`${urls.paymentBase}/${id}`, 'PATCH', { is_default: true, status: 'active' });
                if (type === 'payment-disable') await mutate(`${urls.paymentBase}/${id}`, 'PATCH', { status: 'disabled', is_default: false });
                if (type === 'payment-enable') await mutate(`${urls.paymentBase}/${id}`, 'PATCH', { status: 'active' });
                if (type === 'debt-paid') await mutate(`${urls.debts}/${id}/status`, 'PATCH', { status: 'paid' });
                if (type === 'debt-unpaid') await mutate(`${urls.debts}/${id}/status`, 'PATCH', { status: 'unpaid' });
                if (type.startsWith('user-')) await mutate(`${urls.users}/${id}/status`, 'PATCH', { status: type.substring(5) });
                if (type === 'notify-test') await mutate(`${urls.notifications}/${id}/test`);
                if (type === 'notify-disable') await mutate(`${urls.notifications}/${id}`, 'DELETE');
                await load(document.querySelector('.tab.is-active')?.dataset.tab || 'campaigns');
            } catch (error) { showNotice(error.message, true); }
        });
        load(new URLSearchParams(window.location.search).get('tab') || 'campaigns');
    </script>
@endpush
