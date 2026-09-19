@php
    $campaignStatusValue =
        $campaign->status instanceof \BackedEnum ? $campaign->status->value : (string) $campaign->status;
    $isDraft = $campaignStatusValue === 'draft';
    $isCampaignClosed = $campaignStatusValue === 'closed';
    $isCampaignLive = $campaignStatusValue === 'active';
@endphp

<x-admin.layout :title="__('admin.brand_title') . ' · ' . $campaign->name" active="campaigns" :room="$room">
    <div id="campaign-app" class="space-y-6">
        <script>
        window.__campaignOrderCheckUrl = {{ Js::from($orderCheckUrl) }};
        window.__campaignGrossSubtotal = {{ (int) ($grossSubtotal ?? 0) }};
        window.__campaignDeliveryFee = {{ (int) ($campaign->delivery_fee ?? 0) }};
        window.__campaignDiscount = {{ (int) ($campaign->discount ?? 0) }};
        window.__campaignOrderStatus = {{ Js::from($orders->mapWithKeys(fn($order) => [(string) $order->id => $order->status->value])) }};
        window.__campaignOrdersData = {{ Js::from(
            $orders->map(
                fn($o) => [
                    'id' => $o->id,
                    'code' => $o->code,
                    'status' => $o->status->value,
                    'created_at' => $o->created_at?->format('d/m/Y H:i') ?? '',
                    'user_name' => $o->roomUser?->display_name ?? __('admin.member'),
                    'user_code' => $o->roomUser?->user_code,
                    'email' => $o->roomUser?->globalUser?->email,
                    'desk_location' => $o->roomUser?->globalUser?->desk_location,
                    'subtotal' => (int) $o->subtotal,
                    'sponsor_amount' => (int) $o->sponsor_amount,
                    'delivery_fee' => (int) ($o->delivery_fee ?? 0),
                    'discount' => (int) ($o->discount ?? 0),
                    'final_amount' => (int) $o->final_amount,
                    'note' => $o->note,
                    'items' => $o->items->map(
                        fn($it) => [
                            'id' => $it->id,
                            'name' => $it->item_name,
                            'size' => $it->size_name,
                            'quantity' => (int) $it->quantity,
                            'unit_price' => (int) $it->unit_price,
                            'total_amount' => (int) $it->total_amount,
                            'line_subtotal' => (int) ($it->line_subtotal ?? $it->total_amount),
                            'note' => $it->note,
                            'toppings' => $it->toppings->map(
                                fn($top) => [
                                    'name' => $top->topping_name,
                                    'price' => (int) $top->price,
                                ],
                            ),
                        ],
                    ),
                ],
            ),
        ) }};
        window.__campaignDebtsStatus = {{ Js::from($campaign->debts->mapWithKeys(fn($debt) => [(string) $debt->id => $debt->status instanceof \BackedEnum ? $debt->status->value : (string) $debt->status])) }};
        window.__campaignDebtsData = {{ Js::from(
            $campaign->debts->map(
                fn($d) => [
                    'id' => $d->id,
                    'code' => $d->code,
                    'user_name' => $d->roomUser?->display_name ?? __('admin.member'),
                    'user_code' => $d->roomUser?->user_code,
                    'email' => $d->roomUser?->globalUser?->email,
                    'desk_location' => $d->roomUser?->globalUser?->desk_location,
                    'original_amount' => (int) $d->original_amount,
                    'sponsor_amount' => (int) ($d->sponsor_amount ?? 0),
                    'paid_amount' => (int) ($d->paid_amount ?? 0),
                    'remaining_amount' => (int) ($d->remaining_amount ?? 0),
                    'status' => $d->status instanceof \BackedEnum ? $d->status->value : (string) $d->status,
                    'note' => $d->note ?: $d->code,
                    'created_at' => $d->created_at?->format('d/m/Y H:i') ?? '',
                    'updated_at' => $d->updated_at?->format('d/m/Y H:i') ?? '',
                ],
            ),
        ) }};
        window.__campaignRestaurant = {{ Js::from($campaign->restaurant) }};
        window.__campaignAggregatedItems = {{ Js::from($aggregatedItems) }};
        window.__campaignCode = {{ Js::from($campaign->code) }};

        (function () {
            const orderCheckUrl = window.__campaignOrderCheckUrl;
            const grossSubtotal = window.__campaignGrossSubtotal;
            let deliveryFee = window.__campaignDeliveryFee;
            let discount = window.__campaignDiscount;
            const orderStatus = window.__campaignOrderStatus;
            const ordersData = window.__campaignOrdersData;
            const debtsStatus = window.__campaignDebtsStatus;
            const debtsData = window.__campaignDebtsData;
            const downloadStates = {};
            const isUpdatingDebt = {};
            const isUpdatingOrder = {};
            let selectedOrder = null;
            let selectedDebt = null;

            function formatCurrency(val) {
                const num = Number(val) || 0;
                return new Intl.NumberFormat('vi-VN').format(num) + ' ₫';
            }
            function formatInput(val) {
                if (val === null || val === undefined || val === '') return '';
                const num = Number(val);
                if (isNaN(num)) return '';
                return new Intl.NumberFormat('vi-VN').format(num);
            }
            function parseInput(val) {
                const raw = String(val).replace(/[^\d]/g, '');
                return raw ? parseInt(raw, 10) : 0;
            }
            function filterNumberInput(e) {
                if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End'].includes(e.key)) {
                    return;
                }
                if (e.ctrlKey || e.metaKey) {
                    return;
                }
                if (!/^[0-9.,]$/.test(e.key)) {
                    e.preventDefault();
                }
            }
            window.filterNumberInput = filterNumberInput;

            // ----- Generic modal open/close helpers -----
            function openModal(id) {
                const el = document.getElementById(id);
                if (el) el.style.display = 'flex';
            }
            function closeModal(id) {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            }
            window.openModal = openModal;
            window.closeModal = closeModal;
            window.closeModalOnBackdrop = function (event, id, canClose) {
                if (event.target !== event.currentTarget) return;
                if (typeof canClose === 'function' && !canClose()) return;
                closeModal(id);
            };

            // ----- Action menu dropdown -----
            window.toggleActionMenu = function () {
                const menu = document.getElementById('action-menu-dropdown');
                const chevron = document.getElementById('action-menu-chevron');
                if (!menu) return;
                const isOpen = menu.style.display === 'block';
                menu.style.display = isOpen ? 'none' : 'block';
                if (chevron) chevron.classList.toggle('rotate-180', !isOpen);
            };
            window.closeActionMenu = function () {
                const menu = document.getElementById('action-menu-dropdown');
                const chevron = document.getElementById('action-menu-chevron');
                if (menu) menu.style.display = 'none';
                if (chevron) chevron.classList.remove('rotate-180');
            };
            document.addEventListener('click', function (e) {
                const wrapper = document.getElementById('action-menu-wrapper');
                if (wrapper && !wrapper.contains(e.target)) window.closeActionMenu();
            });

            // ----- Download export buttons -----
            window.downloadExport = async function (event, dataset) {
                event.preventDefault();
                const button = event.currentTarget;
                if (downloadStates[dataset]) return;

                const icon = button.querySelector('[data-download-icon]');
                const label = button.querySelector('[data-download-label]');
                const originalIcon = icon?.textContent ?? 'download';
                const originalLabel = label?.textContent ?? '';
                downloadStates[dataset] = true;
                if (icon) {
                    icon.textContent = 'progress_activity';
                    icon.classList.add('animate-spin');
                }
                if (label) label.textContent = '{{ __('admin.processing') }}...';
                button.classList.add('opacity-60', 'pointer-events-none');
                button.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(button.href, {
                        headers: { 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
                    });
                    if (!response.ok) throw new Error('{{ __('admin.download_failed') }}');

                    const blob = await response.blob();
                    const contentDisposition = response.headers.get('Content-Disposition') || '';
                    const filenameMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)|filename=([^;]+)/i);
                    const filenameValue = filenameMatch?.[1] || filenameMatch?.[2] || '';
                    const filename = filenameValue ?
                        decodeURIComponent(filenameValue).replaceAll(String.fromCharCode(34), '') :
                        `campaign-${dataset}.xlsx`;
                    const objectUrl = URL.createObjectURL(blob);
                    const downloadLink = document.createElement('a');
                    downloadLink.href = objectUrl;
                    downloadLink.download = filename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                    URL.revokeObjectURL(objectUrl);
                } catch (error) {
                    alert(error.message || '{{ __('admin.download_failed') }}');
                } finally {
                    downloadStates[dataset] = false;
                    if (icon) {
                        icon.textContent = originalIcon;
                        icon.classList.remove('animate-spin');
                    }
                    if (label) label.textContent = originalLabel;
                    button.classList.remove('opacity-60', 'pointer-events-none');
                    button.removeAttribute('aria-busy');
                }
            };

            // ----- Order detail modal -----
            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function renderOrderDetail(order) {
                const setText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val ?? '';
                };
                const setShow = (id, show) => {
                    const el = document.getElementById(id);
                    if (el) el.style.display = show ? '' : 'none';
                };
                setText('order-detail-code', order.code);
                setShow('order-detail-created-at-wrap', !!order.created_at);
                setText('order-detail-created-at', order.created_at);
                setText('order-detail-user-name', order.user_name);
                setShow('order-detail-user-code-wrap', !!order.user_code);
                setText('order-detail-user-code', order.user_code ? ('#' + order.user_code) : '');
                setShow('order-detail-email-wrap', !!order.email);
                setText('order-detail-email', order.email);
                setShow('order-detail-desk-wrap', !!order.desk_location);
                setText('order-detail-desk', order.desk_location);
                setShow('order-detail-note-wrap', !!order.note);
                setText('order-detail-note', order.note);
                setText('order-detail-subtotal', formatCurrency(order.subtotal));
                setShow('order-detail-sponsor-wrap', order.sponsor_amount > 0);
                setText('order-detail-sponsor', '-' + formatCurrency(order.sponsor_amount));
                setText('order-detail-final', formatCurrency(order.final_amount));

                const itemsContainer = document.getElementById('order-detail-items');
                if (itemsContainer) {
                    itemsContainer.innerHTML = (order.items || []).map(item => {
                        const toppingsHtml = (item.toppings && item.toppings.length) ? `
                            <div class="flex flex-wrap gap-1 pt-1">
                                ${item.toppings.map(top => `
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-surface-container text-[10px] text-outline border border-outline-variant/50">
                                        + ${escapeHtml(top.name)}${top.price > 0 ? ' (' + formatCurrency(top.price) + ')' : ''}
                                    </span>
                                `).join('')}
                            </div>
                        ` : '';
                        const noteHtml = item.note ? `
                            <div class="text-[11px] text-amber-800 bg-amber-50/80 px-2 py-0.5 rounded border border-amber-200/60 mt-1 italic">
                                📝 ${escapeHtml(item.note)}
                            </div>
                        ` : '';
                        return `
                            <div class="p-3 flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-on-surface text-sm">
                                        ${escapeHtml(item.name)}${item.size ? ' <span class="text-outline font-normal">(' + escapeHtml(item.size) + ')</span>' : ''}
                                    </div>
                                    ${toppingsHtml}
                                    ${noteHtml}
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-on-surface font-mono">${formatCurrency(item.line_subtotal || item.total_amount)}</div>
                                    <div class="text-[11px] text-outline font-mono">${item.quantity} x ${formatCurrency(item.unit_price)}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }

            window.openOrderDetail = function (orderId) {
                selectedOrder = ordersData.find(o => o.id === orderId) || null;
                if (!selectedOrder) return;
                renderOrderDetail(selectedOrder);
                openModal('modal-order-detail');
            };
            window.closeOrderDetail = function () {
                closeModal('modal-order-detail');
            };

            window.copyOrderCode = async function (code, event) {
                const button = event.currentTarget;
                const icon = button.querySelector('.material-symbols-outlined');
                try {
                    await navigator.clipboard.writeText(code);
                    if (icon) icon.textContent = 'check';
                    button.classList.add('text-primary');
                    window.setTimeout(() => {
                        if (icon) icon.textContent = 'content_copy';
                        button.classList.remove('text-primary');
                    }, 2000);
                } catch (error) {
                    if (window.notify) window.notify('{{ addslashes(__('admin.copy_failed')) }}', 'error');
                }
            };

            // ----- Debt detail modal -----
            const debtStatusClassMap = {
                paid: 'bg-emerald-100 text-emerald-700 border-emerald-300',
                waived: 'bg-blue-100 text-blue-700 border-blue-300',
                pending: 'bg-amber-100 text-amber-700 border-amber-300',
            };

            function renderDebtDetail(debt) {
                const setText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val ?? '';
                };
                const setShow = (id, show) => {
                    const el = document.getElementById(id);
                    if (el) el.style.display = show ? '' : 'none';
                };
                setText('debt-detail-code', debt.code);
                setText('debt-detail-user-name', debt.user_name);
                setShow('debt-detail-user-code-wrap', !!debt.user_code);
                setText('debt-detail-user-code', debt.user_code ? ('#' + debt.user_code) : '');
                setShow('debt-detail-email-wrap', !!debt.email);
                setText('debt-detail-email', debt.email);
                setShow('debt-detail-desk-wrap', !!debt.desk_location);
                setText('debt-detail-desk', debt.desk_location);
                setShow('debt-detail-note-wrap', !!debt.note);
                setText('debt-detail-note', debt.note);
                setText('debt-detail-original', formatCurrency(debt.original_amount));
                setShow('debt-detail-sponsor-wrap', debt.sponsor_amount > 0);
                setText('debt-detail-sponsor', '-' + formatCurrency(debt.sponsor_amount));
                setText('debt-detail-paid', formatCurrency(debt.paid_amount));
                setText('debt-detail-remaining', formatCurrency(debt.remaining_amount));
                setText('debt-detail-created-at', debt.created_at);
                setText('debt-detail-updated-at', debt.updated_at);

                const remainingCard = document.getElementById('debt-detail-remaining-card');
                if (remainingCard) {
                    remainingCard.classList.remove('bg-amber-50', 'border-amber-200', 'bg-emerald-50', 'border-emerald-200');
                    if (debt.remaining_amount > 0) {
                        remainingCard.classList.add('bg-amber-50', 'border-amber-200');
                    } else {
                        remainingCard.classList.add('bg-emerald-50', 'border-emerald-200');
                    }
                }
                const select = document.getElementById('debt-detail-status-select');
                if (select) select.value = debt.status;
            }

            window.openDebtDetail = function (debtId) {
                selectedDebt = debtsData.find(d => d.id === debtId) || null;
                if (!selectedDebt) return;
                renderDebtDetail(selectedDebt);
                openModal('modal-debt-detail');
            };
            window.closeDebtDetail = function () {
                closeModal('modal-debt-detail');
            };

            function applyDebtRowStatusClasses(selectEl, status) {
                if (!selectEl) return;
                Object.values(debtStatusClassMap).forEach(cls => {
                    cls.split(' ').forEach(c => selectEl.classList.remove(c));
                });
                const classes = debtStatusClassMap[status] || debtStatusClassMap.pending;
                classes.split(' ').forEach(c => selectEl.classList.add(c));
            }

            async function updateDebtStatus(debtId, newStatus, selectEl) {
                isUpdatingDebt[debtId] = true;
                if (selectEl) selectEl.disabled = true;
                const rowSpinner = document.getElementById('debt-row-spinner-' + debtId);
                if (rowSpinner) rowSpinner.style.display = '';
                try {
                    const url = '{{ route('admin.debts.status', [$room, ':debtId']) }}'.replace(':debtId', debtId);
                    await dfApi(url, {
                        method: 'PATCH',
                        body: { status: newStatus }
                    });
                    debtsStatus[debtId] = newStatus;
                    const targetDebt = debtsData.find(d => d.id === debtId);
                    if (targetDebt) {
                        targetDebt.status = newStatus;
                        if (newStatus === 'paid') {
                            targetDebt.paid_amount += targetDebt.remaining_amount;
                            targetDebt.remaining_amount = 0;
                        } else if (newStatus === 'waived') {
                            targetDebt.remaining_amount = 0;
                        }
                        if (selectedDebt && selectedDebt.id === debtId) {
                            renderDebtDetail(targetDebt);
                        }
                    }
                    if (selectEl) applyDebtRowStatusClasses(selectEl, newStatus);
                } catch (err) {
                    alert(err.message || 'Lỗi khi cập nhật trạng thái nợ');
                    if (selectEl) selectEl.value = debtsStatus[debtId];
                } finally {
                    isUpdatingDebt[debtId] = false;
                    if (selectEl) selectEl.disabled = false;
                    if (rowSpinner) rowSpinner.style.display = 'none';
                }
            }
            window.updateDebtStatusFromRow = function (debtId, selectEl) {
                updateDebtStatus(debtId, selectEl.value, selectEl);
            };
            window.updateDebtStatusFromDetail = function (debtId, selectEl) {
                updateDebtStatus(debtId, selectEl.value, document.getElementById('debt-row-select-' + debtId));
            };

            // ----- Order confirmation toggle -----
            window.toggleOrderConfirmation = async function (orderId) {
                const currentStatus = orderStatus[orderId];
                const nextStatus = currentStatus === 'confirmed' ? 'submitted' : 'confirmed';
                isUpdatingOrder[orderId] = true;
                const spinner = document.getElementById('order-switch-spinner-' + orderId);
                const switchLabel = document.getElementById('order-switch-label-' + orderId);
                if (spinner) spinner.style.display = '';
                if (switchLabel) switchLabel.style.display = 'none';
                try {
                    const url = '{{ route('admin.orders.status', [$room, ':orderId']) }}'.replace(':orderId', orderId);
                    await dfApi(url, {
                        method: 'PATCH',
                        body: { status: nextStatus }
                    });
                    orderStatus[orderId] = nextStatus;
                    const targetOrder = ordersData.find(o => o.id === orderId);
                    if (targetOrder) {
                        targetOrder.status = nextStatus;
                    }
                    const orderCode = targetOrder?.code || '';
                    const msg = nextStatus === 'confirmed' ?
                        '{{ addslashes(__('admin.order_confirmed_success_toast', ['code' => ':code'])) }}'.replace(':code', orderCode) :
                        '{{ addslashes(__('admin.order_unconfirmed_success_toast', ['code' => ':code'])) }}'.replace(':code', orderCode);
                    if (window.notify) {
                        window.notify(msg, 'success');
                    }
                    const checkbox = document.getElementById('order-switch-checkbox-' + orderId);
                    if (checkbox) checkbox.checked = nextStatus === 'confirmed';
                } catch (err) {
                    if (window.notify) {
                        window.notify(err.message || '{{ addslashes(__('admin.order_status_update_failed')) }}', 'error');
                    } else {
                        alert(err.message || 'Lỗi khi cập nhật trạng thái đơn');
                    }
                    const checkbox = document.getElementById('order-switch-checkbox-' + orderId);
                    if (checkbox) checkbox.checked = currentStatus === 'confirmed';
                } finally {
                    isUpdatingOrder[orderId] = false;
                    if (spinner) spinner.style.display = 'none';
                    if (switchLabel) switchLabel.style.display = '';
                }
            };

            // ----- Adjust delivery fee / discount modal -----
            function setLoadingButton(idPrefix, loading) {
                const btn = document.getElementById(idPrefix + '-btn');
                const normal = document.getElementById(idPrefix + '-normal');
                const loadingEl = document.getElementById(idPrefix + '-loading');
                if (btn) btn.disabled = loading;
                if (normal) normal.style.display = loading ? 'none' : '';
                if (loadingEl) loadingEl.style.display = loading ? '' : 'none';
            }

            window.openAdjustFeeModal = function () {
                const feeInput = document.getElementById('adjust-fee-input');
                const discountInput = document.getElementById('adjust-discount-input');
                if (feeInput) feeInput.value = formatInput(deliveryFee);
                if (discountInput) discountInput.value = formatInput(discount);
                updateDiscountHint();
                openModal('modal-adjust-fee');
            };
            window.closeAdjustFeeModal = function () {
                closeModal('modal-adjust-fee');
            };
            window.onAdjustFeeInput = function (input) {
                deliveryFee = parseInput(input.value);
                updateDiscountHint();
            };
            window.onAdjustDiscountInput = function (input) {
                discount = parseInput(input.value);
                updateDiscountHint();
            };
            function updateDiscountHint() {
                const maxDiscount = grossSubtotal + Number(deliveryFee);
                const hintEl = document.getElementById('adjust-discount-hint');
                const discountInput = document.getElementById('adjust-discount-input');
                const overLimit = grossSubtotal > 0 && Number(discount) > maxDiscount;
                if (hintEl) {
                    hintEl.textContent = '{{ __('admin.max_discount_hint') }}'.replace(':amount', formatCurrency(maxDiscount)) || ('Tối đa: ' + formatCurrency(maxDiscount));
                }
                if (discountInput) {
                    discountInput.classList.toggle('border-red-500', overLimit);
                    discountInput.classList.toggle('border-outline-variant', !overLimit);
                }
            }
            window.saveAdjustments = async function () {
                const maxDiscount = grossSubtotal + Number(deliveryFee);
                if (grossSubtotal > 0 && Number(discount) > maxDiscount) {
                    alert('{{ addslashes(__('admin.discount_cannot_exceed_subtotal_plus_fee_prompt')) }}' + formatCurrency(maxDiscount));
                    return;
                }
                setLoadingButton('adjust-fee-save', true);
                try {
                    await dfApi('{{ route('admin.campaigns.update', [$room, $campaign]) }}', {
                        method: 'PATCH',
                        body: {
                            delivery_fee: Number(deliveryFee) || 0,
                            discount: Number(discount) || 0
                        }
                    });
                    window.location.reload();
                } catch (err) {
                    alert(err.message || 'Lỗi khi cập nhật phụ phí và giảm giá');
                    setLoadingButton('adjust-fee-save', false);
                }
            };

            // ----- Cancel campaign modal -----
            window.openConfirmCancelModal = function () {
                openModal('modal-confirm-cancel');
            };
            window.closeConfirmCancelModal = function () {
                closeModal('modal-confirm-cancel');
            };
            window.cancelCampaign = async function () {
                setLoadingButton('confirm-cancel-save', true);
                try {
                    await dfApi('{{ route('admin.campaigns.cancel', [$room, $campaign]) }}', {
                        method: 'POST'
                    });
                    window.location.href = '{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}';
                } catch (err) {
                    alert(err.message || 'Lỗi khi hủy chiến dịch');
                    setLoadingButton('confirm-cancel-save', false);
                }
            };

            // ----- Close campaign modal -----
            window.openCloseConfirmModal = function () {
                openModal('modal-close-confirm');
            };
            window.closeCloseConfirmModal = function () {
                const btn = document.getElementById('close-confirm-save-btn');
                if (btn && btn.disabled) return;
                closeModal('modal-close-confirm');
            };
            window.executeCloseCampaign = async function () {
                setLoadingButton('close-confirm-save', true);
                const allowDebtCheckbox = document.getElementById('allow-debt-checkbox');
                try {
                    await dfApi('{{ route('admin.campaigns.close', [$room, $campaign]) }}', {
                        method: 'POST',
                        body: { allow_debt: allowDebtCheckbox ? allowDebtCheckbox.checked : true }
                    });
                    window.location.reload();
                } catch (err) {
                    alert(err.message || 'Lỗi khi đóng chiến dịch');
                    setLoadingButton('close-confirm-save', false);
                }
            };

            // ----- Confirm delivery modal -----
            window.openConfirmDeliveryModal = function () {
                openModal('modal-confirm-delivery');
            };
            window.closeConfirmDeliveryModal = function () {
                closeModal('modal-confirm-delivery');
            };
            window.executeMarkDelivering = async function () {
                setLoadingButton('confirm-delivery-save', true);
                try {
                    await dfApi('{{ route('admin.campaigns.mark-delivering', [$room, $campaign]) }}', {
                        method: 'POST'
                    });
                    window.location.reload();
                } catch (err) {
                    alert(err.message || 'Lỗi khi cập nhật trạng thái giao hàng');
                    setLoadingButton('confirm-delivery-save', false);
                }
            };

            // ----- Misc actions tied to root scope -----
            window.reloadData = function (event) {
                const btn = event ? event.currentTarget : document.getElementById('reload-data-btn');
                if (btn) btn.disabled = true;
                window.location.reload();
            };
            window.copyOrderCheckLink = async function (event) {
                if (!orderCheckUrl) {
                    alert('{{ addslashes(__('admin.order_check_link_unavailable')) }}');
                    return;
                }
                const btn = event ? event.currentTarget : null;
                if (btn) btn.disabled = true;
                try {
                    await navigator.clipboard.writeText(orderCheckUrl);
                } catch (error) {
                    alert('{{ addslashes(__('admin.order_check_link_unavailable')) }}');
                } finally {
                    if (btn) btn.disabled = false;
                }
            };
            window.confirmAllOrders = async function (event) {
                const orderIds = ordersData
                    .filter(order => order.status === 'submitted')
                    .map(order => order.id);
                if (!orderIds.length) {
                    if (window.notify) window.notify('{{ addslashes(__('admin.no_orders_to_confirm')) }}', 'info');
                    return;
                }
                if (!window.confirm('{{ addslashes(__('admin.confirm_all_orders_prompt')) }}')) return;

                const btn = event ? event.currentTarget : null;
                if (btn) btn.disabled = true;
                try {
                    await dfApi('{{ route('admin.orders.bulk-status', [$room]) }}', {
                        method: 'POST',
                        body: { order_ids: orderIds, status: 'confirmed' }
                    });
                    orderIds.forEach(orderId => {
                        orderStatus[orderId] = 'confirmed';
                        const order = ordersData.find(item => item.id === orderId);
                        if (order) order.status = 'confirmed';
                        const checkbox = document.getElementById('order-switch-checkbox-' + orderId);
                        if (checkbox) checkbox.checked = true;
                    });
                    if (window.notify) window.notify('{{ addslashes(__('admin.bulk_orders_confirmed_success')) }}', 'success');
                } catch (error) {
                    if (window.notify) window.notify(error.message || '{{ addslashes(__('admin.order_status_update_failed')) }}', 'error');
                    else alert(error.message || '{{ addslashes(__('admin.order_status_update_failed')) }}');
                } finally {
                    if (btn) btn.disabled = false;
                }
            };
            window.copySummary = function () {
                const restaurant = window.__campaignRestaurant;
                const items = window.__campaignAggregatedItems;
                let text = `📦 ĐƠN ĐẶT HÀNG: ${restaurant}\n`;
                text += `Mã chiến dịch: #${window.__campaignCode}\n`;
                text += `--------------------------------\n`;
                items.forEach((item, idx) => {
                    text += `${idx + 1}. ${item.name}${item.size ? ' (' + item.size + ')' : ''} x ${item.quantity} phần (${new Intl.NumberFormat('vi-VN').format(item.unit_price)}đ)\n`;
                    if (item.notes && item.notes.length) {
                        text += `   📝 Ghi chú: ${item.notes.join(', ')}\n`;
                    }
                });
                text += `--------------------------------\n`;
                text += `Tổng số lượng: ${items.reduce((acc, it) => acc + it.quantity, 0)} phần\n`;
                text += `Tổng tiền hàng: ${new Intl.NumberFormat('vi-VN').format(grossSubtotal)}đ\n`;
                navigator.clipboard.writeText(text).then(() => {
                    alert('{{ addslashes(__('admin.copied_to_clipboard')) }}');
                }).catch(() => {
                    alert('Không thể sao chép tự động vào clipboard');
                });
            };
        })();
        </script>

        <div class="flex items-center gap-2 border-b border-outline-variant/40 pb-2">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'overview']) }}"
                class="px-3 py-2 text-xs font-semibold {{ request('tab', 'overview') !== 'items' ? 'text-primary border-b-2 border-primary' : 'text-outline' }}">{{ __('admin.campaign_overview_tab') }}</a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'items']) }}"
                class="px-3 py-2 text-xs font-semibold {{ request('tab') === 'items' ? 'text-primary border-b-2 border-primary' : 'text-outline' }}">{{ __('admin.campaign_items_tab') }}</a>
        </div>

        @if (request('tab') === 'items')
            @php
                $categories = $campaign->items->pluck('category')->filter()->unique()->sort()->values();
                $itemsJson = $campaign->items->map(
                    fn($it) => [
                        'id' => $it->id,
                        'name' => $it->name,
                        'category' => $it->category ?: '',
                        'base_price' => (float) $it->base_price,
                        'image_url' => $it->image_url,
                        'status' => $it->status?->value ?? (string) $it->status,
                        'initial_status' => $it->status?->value ?? (string) $it->status,
                    ],
                );
            @endphp
            <section id="campaign-items-section"
                class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-on-surface">{{ __('admin.campaign_items_tab') }}</h2>
                            <span
                                class="px-2 py-0.5 rounded-full text-xs font-semibold bg-surface-container text-outline font-mono"
                                id="items-count-badge">{{ $itemsJson->count() }} {{ __('admin.portions') }}</span>
                        </div>
                        <p class="text-xs text-outline mt-0.5">{{ __('admin.campaign_items_manage_description') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Category Filter Dropdown (Client-side JS) -->
                        <div class="flex items-center gap-1.5">
                            <label
                                class="text-xs font-semibold text-outline">{{ __('admin.source_category') }}:</label>
                            <select id="category-filter-select" onchange="onCategoryFilterChange(this.value)"
                                class="h-9 px-3 bg-surface border border-outline-variant rounded-lg text-xs font-semibold focus:border-primary outline-hidden transition-colors cursor-pointer">
                                <option value="all">{{ __('admin.filter_all') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Changes Button (if any changes) -->
                        <button type="button" id="reset-changes-btn" onclick="resetItemChanges()"
                            class="h-9 px-3 rounded-lg border border-outline-variant hover:bg-surface-container text-outline hover:text-on-surface text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                            style="display: none;">
                            <span class="material-symbols-outlined text-[16px]">undo</span>
                            <span>{{ __('admin.reset_changes') }}</span>
                        </button>

                        <!-- Batch Update Button -> Open Confirm Modal -->
                        <button type="button" id="items-save-btn" onclick="openItemsConfirmModal()" disabled
                            class="h-9 px-4 rounded-lg bg-primary hover:bg-primary/90 text-on-primary text-xs font-semibold flex items-center gap-2 transition-all shadow-xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">save</span>
                            <span>{{ __('admin.save_items_status_btn') }}</span>
                            <span id="items-changed-badge"
                                class="px-1.5 py-0.2 rounded-full bg-white text-primary text-[10px] font-bold"
                                style="display: none;"></span>
                        </button>
                    </div>
                </div>

                <!-- Items Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3" id="items-grid">
                    @foreach ($itemsJson as $it)
                        <div class="border rounded-lg p-3 flex items-center gap-3 transition-colors border-outline-variant bg-surface"
                            id="item-card-{{ $it['id'] }}" data-item-id="{{ $it['id'] }}"
                            data-item-category="{{ $it['category'] }}">
                            <div
                                class="w-12 h-12 rounded bg-surface-container overflow-hidden shrink-0 relative flex items-center justify-center">
                                <span
                                    class="material-symbols-outlined text-outline-variant text-[20px]">restaurant</span>
                                @if ($it['image_url'])
                                    <img src="{{ $it['image_url'] }}" alt="{{ $it['name'] }}" loading="lazy"
                                        onload="this.hidden = false" onerror="this.hidden = true"
                                        class="absolute inset-0 w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm truncate text-on-surface">{{ $it['name'] }}</div>
                                <div class="text-xs text-outline">
                                    <span>{{ $it['category'] ?: __('admin.filter_all') }}</span> ·
                                    <span class="font-mono font-medium text-on-surface">{{ number_format($it['base_price'], 0, ',', '.') }} ₫</span>
                                </div>
                                <div id="item-changed-indicator-{{ $it['id'] }}"
                                    class="text-[10px] font-semibold mt-0.5 flex items-center gap-1" style="display: none;">
                                    <span class="w-1.5 h-1.5 rounded-full" id="item-changed-dot-{{ $it['id'] }}"></span>
                                    <span id="item-changed-label-{{ $it['id'] }}"></span>
                                </div>
                            </div>
                            <button type="button" role="switch" id="item-toggle-{{ $it['id'] }}"
                                aria-checked="{{ $it['status'] === 'active' ? 'true' : 'false' }}"
                                onclick="toggleItemStatus({{ $it['id'] }})"
                                data-item-status="{{ $it['status'] }}" data-item-initial-status="{{ $it['status'] }}"
                                class="relative inline-flex h-6 w-11 rounded-full transition-colors cursor-pointer shrink-0 {{ $it['status'] === 'active' ? 'bg-primary' : 'bg-outline-variant' }}">
                                <span id="item-toggle-dot-{{ $it['id'] }}"
                                    class="inline-block h-5 w-5 mt-0.5 rounded-full bg-white shadow transition-transform {{ $it['status'] === 'active' ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                            </button>
                        </div>
                    @endforeach

                    <div id="items-empty-state" class="col-span-full py-12 text-center space-y-2" style="display: none;">
                        <span
                            class="material-symbols-outlined text-[36px] text-outline-variant">restaurant_menu</span>
                        <p class="text-sm text-outline">{{ __('admin.no_campaign_items') }}</p>
                    </div>
                </div>

                <!-- MODAL: CONFIRM BATCH UPDATE ITEMS STATUS -->
                <div id="modal-items-confirm"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                    style="display: none;" onclick="closeModalOnBackdrop(event, 'modal-items-confirm', () => !window.__itemsIsSaving)">
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col" onclick="event.stopPropagation()">
                        <div
                            class="p-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">checklist</span>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-on-surface">
                                        {{ __('admin.confirm_update_items_status_title') }}
                                    </h3>
                                    <p class="text-xs text-outline font-mono">#{{ $campaign->code }} ·
                                        {{ $campaign->name }}</p>
                                </div>
                            </div>
                            <button type="button" id="items-confirm-close-btn" onclick="closeItemsConfirmModal()"
                                class="text-outline hover:text-on-surface disabled:opacity-40 cursor-pointer">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <div class="p-5 space-y-4 text-xs">
                            <p class="text-on-surface leading-relaxed" id="items-confirm-desc">
                            </p>

                            <!-- Breakdown stats -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-950">
                                    <span
                                        class="text-[11px] text-emerald-800 font-medium block">{{ __('admin.will_be_activated') }}</span>
                                    <span class="text-lg font-bold font-mono mt-0.5 block text-emerald-700"
                                        id="items-confirm-activated-count">
                                    </span>
                                </div>
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-950">
                                    <span
                                        class="text-[11px] text-amber-800 font-medium block">{{ __('admin.will_be_hidden') }}</span>
                                    <span class="text-lg font-bold font-mono mt-0.5 block text-amber-700"
                                        id="items-confirm-hidden-count">
                                    </span>
                                </div>
                            </div>

                            <!-- Modal Actions -->
                            <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                                <button type="button" id="items-confirm-cancel-btn" onclick="closeItemsConfirmModal()"
                                    class="px-4 py-2.5 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50">
                                    {{ __('admin.cancel') }}
                                </button>
                                <button type="button" id="items-confirm-save-btn" onclick="executeSaveBatchStatus()"
                                    class="px-4 py-2.5 rounded-lg bg-primary hover:bg-primary/90 text-on-primary text-xs font-semibold flex items-center gap-2 transition-colors cursor-pointer shadow-xs disabled:opacity-50">
                                    <span id="items-confirm-save-loading" class="flex items-center gap-1.5" style="display: none;">
                                        <svg class="animate-spin h-4 w-4 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>{{ __('admin.processing') }}</span>
                                    </span>
                                    <span id="items-confirm-save-normal" class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        <span>{{ __('admin.confirm_update_btn') }}</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <script>
                (function () {
                    'use strict';
                    const itemsData = {{ Js::from($itemsJson) }};
                    let selectedCategory = 'all';
                    window.__itemsIsSaving = false;

                    function changedCount() {
                        return itemsData.filter(item => item.status !== item.initial_status).length;
                    }

                    function updateItemCardVisualState(item) {
                        const card = document.getElementById('item-card-' + item.id);
                        const toggle = document.getElementById('item-toggle-' + item.id);
                        const dot = document.getElementById('item-toggle-dot-' + item.id);
                        const indicator = document.getElementById('item-changed-indicator-' + item.id);
                        const dotIndicator = document.getElementById('item-changed-dot-' + item.id);
                        const label = document.getElementById('item-changed-label-' + item.id);
                        const changed = item.status !== item.initial_status;

                        if (card) {
                            card.classList.toggle('border-primary/50', changed);
                            card.classList.toggle('bg-primary/5', changed);
                            card.classList.toggle('shadow-2xs', changed);
                            card.classList.toggle('border-outline-variant', !changed);
                            card.classList.toggle('bg-surface', !changed);
                        }
                        if (toggle) {
                            toggle.setAttribute('aria-checked', item.status === 'active' ? 'true' : 'false');
                            toggle.classList.toggle('bg-primary', item.status === 'active');
                            toggle.classList.toggle('bg-outline-variant', item.status !== 'active');
                        }
                        if (dot) {
                            dot.classList.toggle('translate-x-5', item.status === 'active');
                            dot.classList.toggle('translate-x-0.5', item.status !== 'active');
                        }
                        if (indicator) {
                            indicator.style.display = changed ? '' : 'none';
                            if (label) label.textContent = item.status === 'active' ? '{{ addslashes(__('admin.will_be_activated')) }}' : '{{ addslashes(__('admin.will_be_hidden')) }}';
                            if (dotIndicator) {
                                dotIndicator.classList.toggle('bg-primary', item.status === 'active');
                                dotIndicator.classList.toggle('bg-amber-600', item.status !== 'active');
                            }
                            if (label) {
                                label.classList.toggle('text-primary', false);
                            }
                            if (indicator) {
                                indicator.classList.toggle('text-primary', item.status === 'active');
                                indicator.classList.toggle('text-amber-700', item.status !== 'active');
                            }
                        }
                    }

                    function refreshSaveControls() {
                        const count = changedCount();
                        const resetBtn = document.getElementById('reset-changes-btn');
                        const saveBtn = document.getElementById('items-save-btn');
                        const badge = document.getElementById('items-changed-badge');
                        if (resetBtn) resetBtn.style.display = count > 0 ? '' : 'none';
                        if (saveBtn) saveBtn.disabled = count === 0;
                        if (badge) {
                            badge.style.display = count > 0 ? '' : 'none';
                            badge.textContent = count;
                        }
                    }

                    function refreshItemsGrid() {
                        let visibleCount = 0;
                        itemsData.forEach(item => {
                            const card = document.getElementById('item-card-' + item.id);
                            if (!card) return;
                            const matchCat = selectedCategory === 'all' || item.category === selectedCategory;
                            card.style.display = matchCat ? '' : 'none';
                            if (matchCat) visibleCount++;
                        });
                        const emptyState = document.getElementById('items-empty-state');
                        if (emptyState) emptyState.style.display = visibleCount === 0 ? '' : 'none';
                    }

                    window.onCategoryFilterChange = function (value) {
                        selectedCategory = value;
                        refreshItemsGrid();
                    };

                    window.toggleItemStatus = function (id) {
                        const item = itemsData.find(it => it.id === id);
                        if (!item) return;
                        item.status = item.status === 'active' ? 'inactive' : 'active';
                        updateItemCardVisualState(item);
                        refreshSaveControls();
                    };

                    window.resetItemChanges = function () {
                        itemsData.forEach(item => {
                            item.status = item.initial_status;
                            updateItemCardVisualState(item);
                        });
                        refreshSaveControls();
                    };

                    window.openItemsConfirmModal = function () {
                        if (changedCount() === 0) {
                            if (window.notify) window.notify('{{ addslashes(__('admin.no_changes_to_save')) }}', 'info');
                            return;
                        }
                        const desc = document.getElementById('items-confirm-desc');
                        if (desc) {
                            desc.textContent = '{{ addslashes(__('admin.confirm_update_items_status_desc', ['count' => ':count'])) }}'.replace(':count', changedCount());
                        }
                        const activatedCount = itemsData.filter(i => i.status !== i.initial_status && i.status === 'active').length;
                        const hiddenCount = itemsData.filter(i => i.status !== i.initial_status && i.status === 'inactive').length;
                        const activatedEl = document.getElementById('items-confirm-activated-count');
                        const hiddenEl = document.getElementById('items-confirm-hidden-count');
                        if (activatedEl) activatedEl.textContent = activatedCount + ' {{ __('admin.portions') }}';
                        if (hiddenEl) hiddenEl.textContent = hiddenCount + ' {{ __('admin.portions') }}';
                        window.openModal('modal-items-confirm');
                    };

                    window.closeItemsConfirmModal = function () {
                        if (window.__itemsIsSaving) return;
                        window.closeModal('modal-items-confirm');
                    };

                    function setItemsSavingState(saving) {
                        window.__itemsIsSaving = saving;
                        const saveBtn = document.getElementById('items-confirm-save-btn');
                        const cancelBtn = document.getElementById('items-confirm-cancel-btn');
                        const closeBtn = document.getElementById('items-confirm-close-btn');
                        const loadingEl = document.getElementById('items-confirm-save-loading');
                        const normalEl = document.getElementById('items-confirm-save-normal');
                        if (saveBtn) saveBtn.disabled = saving;
                        if (cancelBtn) cancelBtn.disabled = saving;
                        if (closeBtn) closeBtn.disabled = saving;
                        if (loadingEl) loadingEl.style.display = saving ? '' : 'none';
                        if (normalEl) normalEl.style.display = saving ? 'none' : '';
                    }

                    window.executeSaveBatchStatus = async function () {
                        const changed = itemsData.filter(item => item.status !== item.initial_status);
                        if (changed.length === 0) {
                            window.closeModal('modal-items-confirm');
                            return;
                        }
                        setItemsSavingState(true);
                        try {
                            const endpoint = '{{ route('admin.campaign-items.batch-status', [$room, $campaign]) }}';
                            const res = await dfApi(endpoint, {
                                method: 'PATCH',
                                body: {
                                    items: changed.map(it => ({ id: it.id, status: it.status }))
                                }
                            });
                            itemsData.forEach(item => {
                                item.initial_status = item.status;
                            });
                            window.closeModal('modal-items-confirm');
                            refreshSaveControls();
                            if (window.notify) {
                                window.notify(res.message || '{{ addslashes(__('admin.campaign_items_batch_updated_success', ['count' => ':count'])) }}'.replace(':count', changed.length), 'success');
                            }
                        } catch (err) {
                            if (window.notify) {
                                window.notify(err.message || '{{ addslashes(__('admin.order_status_update_failed')) }}', 'error');
                            } else {
                                alert(err.message || 'Lỗi khi cập nhật trạng thái món');
                            }
                        } finally {
                            setItemsSavingState(false);
                        }
                    };
                })();
            </script>
        @else
            <!-- SECTION 1: STORE & CAMPAIGN BANNER (2/3 & 1/3 SPLIT LAYOUT) -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                    <!-- Left Column: 2/3 Width - Campaign Info, Tags & Participation -->
                    <div class="lg:col-span-2 space-y-3.5">
                        <div class="flex flex-wrap items-center gap-2">
                            @php
                                $badgeClass = match ($campaign->status?->value) {
                                    'active' => 'bg-error-container border border-error/30 text-on-error-container',
                                    'draft' => 'bg-surface-container-high border border-outline-variant text-outline',
                                    'cancelled' => 'bg-error/10 border border-error/20 text-error',
                                    default => 'bg-emerald-50 border border-emerald-200 text-emerald-800',
                                };
                                $dotClass = match ($campaign->status?->value) {
                                    'active' => 'bg-error status-dot-pulse',
                                    'draft' => 'bg-outline',
                                    'cancelled' => 'bg-error',
                                    default => 'bg-emerald-600',
                                };
                            @endphp
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ $badgeClass }} font-mono text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                                {{ __('admin.status_' . ($campaign->status?->value ?? 'closed')) }}
                            </span>
                            <span
                                class="text-xs text-outline bg-surface-container-low border border-outline-variant px-2 py-0.5 rounded font-mono">
                                #{{ $campaign->code ?? 'N/A' }}
                            </span>
                            <span
                                class="text-xs text-on-surface-variant bg-surface-container-lowest border border-outline-variant px-2 py-0.5 rounded">
                                {{ __('admin.room_label') }} <strong>{{ $room->name }}</strong>
                            </span>
                        </div>

                        <h1 class="text-2xl lg:text-3xl font-bold text-on-surface tracking-tight">
                            {{ $campaign->name }} · {{ $campaign->restaurant }}
                        </h1>

                        @if (!empty($campaign->description))
                            <div
                                class="p-3 rounded-lg bg-surface-container-low/70 border border-outline-variant/60 text-xs text-on-surface-variant leading-relaxed flex items-start gap-2.5">
                                <span
                                    class="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">info</span>
                                <div class="flex-1 min-w-0">
                                    <span
                                        class="font-semibold text-on-surface text-[11px] block uppercase tracking-wider mb-0.5">{{ __('admin.campaign_desc_label') }}</span>
                                    <p class="text-on-surface whitespace-pre-line">{{ $campaign->description }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-outline">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-amber-600">schedule</span>
                                {{ __('admin.deadline_label') }} <strong
                                    class="text-on-surface font-mono">{{ $campaign->deadline?->format('H:i d/m/Y') ?? '11:15' }}</strong>
                            </span>
                            @if (!empty($campaign->max_budget))
                                <span class="text-outline-variant">•</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-primary">payments</span>
                                    {{ __('admin.max_product_budget_ceiling') }}: <strong
                                        class="text-primary font-mono font-semibold">{{ number_format((int) $campaign->max_budget, 0, ',', '.') }}
                                        ₫</strong>
                                </span>
                            @endif
                            <span class="text-outline-variant">•</span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-primary">account_circle</span>
                                {{ __('admin.room_manager_role') }}: <span
                                    class="text-on-surface font-medium">{{ auth('admin')->user()?->name ?? __('admin.breadcrumb_admin') }}</span>
                            </span>
                        </div>

                        <!-- SPONSOR, BUDGET & PAYMENT ACCOUNT TAGS -->
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            @if (false)
                                <!-- Max Budget Tag -->
                                @if (!empty($campaign->max_budget))
                                    <div
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-container-low border border-outline-variant/60 text-xs text-on-surface shadow-2xs">
                                        <span class="material-symbols-outlined text-[16px] text-primary">payments</span>
                                        <span class="text-outline">{{ __('admin.max_product_budget_ceiling') }}:</span>
                                        <span
                                            class="font-mono font-bold text-primary">{{ number_format((int) $campaign->max_budget, 0, ',', '.') }}
                                            ₫</span>
                                    </div>
                                @endif

                            @endif
                            <!-- Sponsor Tags -->
                            @if (!empty($sponsorsList) && $sponsorsList->isNotEmpty())
                                @foreach ($sponsorsList as $sp)
                                    <div
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-medium shadow-2xs">
                                        <span
                                            class="material-symbols-outlined text-[16px] text-emerald-700">redeem</span>
                                        <span class="font-bold text-emerald-950">{{ $sp['name'] }}</span>
                                        @if (($sp['percentage'] ?? 0) > 0)
                                            <span
                                                class="text-[11px] px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 font-semibold font-mono">
                                                {{ $sp['percentage'] }}%
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            @elseif($campaign->sponsor_type === 'full' || !empty($campaign->sponsor_name))
                                <div
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-medium shadow-2xs">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-700">redeem</span>
                                    <span
                                        class="font-bold text-emerald-950">{{ $campaign->sponsor_name ?: __('admin.sponsor_info') }}</span>
                                    <span
                                        class="text-[11px] px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 font-semibold">
                                        {{ $campaign->sponsor_type === 'full' ? __('admin.sponsor_type_full') : __('admin.sponsor_type_custom') }}
                                    </span>
                                </div>
                            @endif

                            <!-- Payment Account Tag -->
                            @if ($campaign->paymentAccount)
                                <div
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-container-low border border-outline-variant/60 text-xs text-on-surface shadow-2xs">
                                    <span
                                        class="material-symbols-outlined text-[16px] text-primary">account_balance</span>
                                    <span class="font-semibold">{{ $campaign->paymentAccount->bank_code }}</span>
                                    <span
                                        class="font-mono text-outline font-semibold">{{ $campaign->paymentAccount->account_number }}</span>
                                    <span
                                        class="text-[11px] text-on-surface-variant font-medium">({{ $campaign->paymentAccount->account_name }})</span>
                                </div>
                            @endif
                        </div>

                        <!-- Room Participation Banner -->
                        @php
                            $totalUsers = max(1, $totalUsersCount ?? 1);
                            $orderedCount = $orderedUsersCount ?? $orders->count();
                            $declinedCount = $declinedUsersCount ?? 0;
                            $pendingCount = max(0, $totalUsers - $orderedCount - $declinedCount);
                            $orderedPercent = round(($orderedCount / $totalUsers) * 100, 1);
                            $declinedPercent = round(($declinedCount / $totalUsers) * 100, 1);
                            $pendingPercent = max(0, 100 - $orderedPercent - $declinedPercent);
                        @endphp
                        <div
                            class="pt-3 border-t border-outline-variant/40 bg-surface-container-low p-3.5 rounded-lg border border-outline-variant/60 flex flex-col gap-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-md bg-emerald-50 border border-emerald-200 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined text-[18px]">pie_chart</span>
                                    </div>
                                    <div>
                                        <span
                                            class="font-semibold text-on-surface text-xs sm:text-sm">{{ __('admin.room_participation_rate') }}</span>
                                        <span
                                            class="text-xs text-outline ml-2 font-mono">({{ $orderedCount }}/{{ $totalUsers }}
                                            {{ __('admin.members') }})</span>
                                    </div>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-primary text-xs font-semibold font-mono">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                    {{ __('admin.participating') }}: {{ $orderedPercent }}%
                                </span>
                            </div>

                            <div class="space-y-1.5">
                                <div
                                    class="h-2.5 w-full bg-surface-container-highest rounded-full overflow-hidden flex shadow-inner">
                                    <div class="bg-primary h-full transition-all duration-300"
                                        style="width: {{ $orderedPercent }}%;"
                                        title="{{ __('admin.ordered') }}: {{ $orderedCount }} ({{ $orderedPercent }}%)">
                                    </div>
                                    <div class="bg-slate-400 h-full transition-all duration-300"
                                        style="width: {{ $declinedPercent }}%;"
                                        title="{{ __('admin.declined') }}: {{ $declinedCount }} ({{ $declinedPercent }}%)">
                                    </div>
                                    <div class="bg-amber-400 h-full transition-all duration-300"
                                        style="width: {{ $pendingPercent }}%;"
                                        title="{{ __('admin.no_response') }}: {{ $pendingCount }} ({{ $pendingPercent }}%)">
                                    </div>
                                </div>
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2 text-xs pt-0.5 font-mono">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="flex items-center gap-1.5 text-primary font-semibold">
                                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                                            {{ $orderedCount }} {{ __('admin.ordered') }} ({{ $orderedPercent }}%)
                                        </span>
                                        <span class="flex items-center gap-1.5 text-outline">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                            {{ $declinedCount }} {{ __('admin.declined') }}
                                            ({{ $declinedPercent }}%)
                                        </span>
                                        <span class="flex items-center gap-1.5 text-amber-700">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                            {{ $pendingCount }} {{ __('admin.no_response') }}
                                            ({{ $pendingPercent }}%)
                                        </span>
                                    </div>
                                    <span class="text-outline">{{ $room->name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: 1/3 Width - Action Dropdown, CSV Export, Back Button -->
                    <div
                        class="lg:col-span-1 flex flex-col gap-2.5 justify-center lg:border-l lg:border-outline-variant/40 lg:pl-6">
                        <!-- Dropdown Action Button -->
                        <div class="relative w-full">
                            <button type="button" @click="actionOpen = !actionOpen"
                                @click.outside="actionOpen = false"
                                class="w-full h-10 px-4 bg-primary hover:bg-primary-container text-on-primary rounded text-xs font-semibold flex items-center justify-between shadow-sm transition-colors cursor-pointer active:scale-[0.99]">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">bolt</span>
                                    <span>{{ __('admin.actions_menu') }}</span>
                                </div>
                                <span class="material-symbols-outlined text-[18px] transition-transform duration-200"
                                    :class="actionOpen ? 'rotate-180' : ''">expand_more</span>
                            </button>

                            <!-- Dropdown Menu Options -->
                            <div x-show="actionOpen" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 left-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xl z-50 py-1.5 divide-y divide-outline-variant/40"
                                style="display: none;">
                                <div class="py-1">
                                    @if ($campaignStatusValue === 'active')
                                        <!-- Kết thúc đơn -->
                                        <button type="button"
                                            @click="actionOpen = false; closeConfirmModalOpen = true"
                                            class="w-full text-left px-3.5 py-2.5 text-xs text-on-surface hover:bg-surface-container-low flex items-center gap-2.5 transition-colors cursor-pointer">
                                            <span
                                                class="material-symbols-outlined text-[18px] text-amber-600">lock</span>
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold">{{ __('admin.close_campaign_action') }}</span>
                                                <span
                                                    class="text-[10px] text-outline">{{ __('admin.step_locked_orders') }}</span>
                                            </div>
                                        </button>
                                    @endif

                                    @if (!in_array($campaignStatusValue, ['closed', 'cancelled'], true))
                                        <!-- Cập nhật thông tin -->
                                        <a href="{{ route('admin.campaigns.edit', [$room, $campaign]) }}"
                                            class="w-full text-left px-3.5 py-2.5 text-xs text-on-surface hover:bg-surface-container-low flex items-center gap-2.5 transition-colors no-underline block">
                                            <span
                                                class="material-symbols-outlined text-[18px] text-primary">edit_square</span>
                                            <div class="flex flex-col">
                                                <span class="font-bold">{{ __('admin.update_campaign_info') }}</span>
                                                <span
                                                    class="text-[10px] text-outline">{{ __('admin.edit_campaign_subtitle', ['room' => $room->name]) }}</span>
                                            </div>
                                        </a>
                                    @endif

                                    @if (!$isDraft)
                                        <!-- Sao chép đơn cho shipper -->
                                        <button type="button" @click="actionOpen = false; copySummary()"
                                            class="w-full text-left px-3.5 py-2.5 text-xs text-on-surface hover:bg-surface-container-low flex items-center gap-2.5 transition-colors cursor-pointer">
                                            <span
                                                class="material-symbols-outlined text-[18px] text-teal-600">content_copy</span>
                                            <div class="flex flex-col">
                                                <span class="font-bold">{{ __('admin.copy_order_shipper') }}</span>
                                                <span
                                                    class="text-[10px] text-outline">{{ __('admin.copy_order_summary') }}</span>
                                            </div>
                                        </button>
                                    @endif
                                </div>

                                @if (!in_array($campaignStatusValue, ['closed', 'cancelled'], true))
                                    <div class="py-1">
                                        <!-- Hủy đơn -->
                                        <button type="button"
                                            @click="actionOpen = false; confirmCancelModalOpen = true"
                                            class="w-full text-left px-3.5 py-2.5 text-xs text-error hover:bg-error-container/20 flex items-center gap-2.5 transition-colors cursor-pointer">
                                            <span
                                                class="material-symbols-outlined text-[18px] text-error">cancel</span>
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold">{{ __('admin.cancel_campaign_action') }}</span>
                                                <span
                                                    class="text-[10px] text-error/70">{{ __('admin.cancel_campaign_action_desc') }}</span>
                                            </div>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if (!$isDraft)
                            <!-- Nút Xuất Bảng Kê CSV -->
                            <button type="button" id="btn-export-statement" data-campaign-id="{{ $campaign->id }}"
                                data-orders="{{ json_encode(
                                    $orders->map(
                                        fn($o) => [
                                            'member' => $o->roomUser?->display_name ?? __('admin.member'),
                                            'code' => $o->roomUser?->user_code ?? '',
                                            'items_count' => $o->items->count(),
                                            'subtotal' => $o->subtotal,
                                            'sponsor_amount' => $o->sponsor_amount,
                                            'final_amount' => $o->final_amount,
                                            'status' => $o->status->value,
                                        ],
                                    ),
                                ) }}"
                                class="w-full h-10 px-4 bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-on-surface rounded text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs cursor-pointer">
                                <span class="material-symbols-outlined text-[18px] text-primary">file_download</span>
                                <span>{{ __('admin.export_settlement_csv') }}</span>
                            </button>
                        @endif

                        <!-- Nút Quay lại danh sách -->
                        <a href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}"
                            class="w-full h-10 px-4 bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-outline hover:text-on-surface rounded text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs no-underline">
                            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                            <span>{{ __('admin.back_to_campaigns') }}</span>
                        </a>

                        <!-- Nút Tải lại dữ liệu -->
                        <button type="button" @click="reloadData()" :disabled="isReloading"
                            class="w-full h-10 px-4 bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-outline hover:text-on-surface rounded text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isReloading" class="material-symbols-outlined text-[18px] text-primary">refresh</span>
                            <span x-show="isReloading" class="material-symbols-outlined animate-spin text-[18px] text-primary"
                                style="display: none;">progress_activity</span>
                            <span x-text="isReloading ? '{{ __('admin.processing') }}...' : '{{ __('admin.reload_data') }}'">{{ __('admin.reload_data') }}</span>
                        </button>

                        @if ($orderCheckUrl)
                            <button type="button" @click="copyOrderCheckLink()" :disabled="isCopyingOrderCheckLink"
                                class="w-full h-10 px-4 bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-outline hover:text-on-surface rounded text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs cursor-pointer disabled:cursor-not-allowed disabled:opacity-60">
                                <span class="material-symbols-outlined text-[18px] text-primary"
                                 :class="{ 'animate-spin': isCopyingOrderCheckLink }"
                                 x-text="isCopyingOrderCheckLink ? 'progress_activity' : 'link'">link</span>
                                <span x-text="isCopyingOrderCheckLink ? '{{ __('admin.processing') }}...' : '{{ __('admin.copy_order_check_link') }}'">{{ __('admin.copy_order_check_link') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
            </section>

            @if (!$isDraft)
                <!-- SECTION 2: 2-COLUMN GRID (FINANCIAL SETTLEMENT + NEXT ACTIONS) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
                    <!-- COLUMN 1: FINANCIAL SETTLEMENT SUMMARY -->
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs overflow-hidden flex flex-col justify-between">
                        <div>
                            <div
                                class="p-4 border-b border-outline-variant/60 bg-surface-container-low flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">calculate</span>
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.financial_settlement_summary') }}
                                    </h3>
                                </div>
                                <button type="button" @click="adjustFeeModalOpen = true" @disabled($isCampaignClosed)
                                    class="px-3 py-1.5 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary/10">
                                    <span class="material-symbols-outlined text-[16px]">tune</span>
                                    <span>{{ __('admin.adjust_fees_discount') }}</span>
                                </button>
                            </div>

                            <div class="p-5 space-y-3.5 text-xs">
                                <!-- Subtotal section -->
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-outline">
                                        <span>{{ __('admin.original_subtotal') }}:</span>
                                        <span
                                            class="font-mono text-on-surface font-semibold text-sm">{{ number_format($grossSubtotal ?? 0, 0, ',', '.') }}
                                            ₫</span>
                                    </div>
                                    <div class="flex justify-between items-center text-outline">
                                        <span class="flex items-center gap-1">
                                            <span>{{ __('admin.delivery_fee_extra') }}:</span>
                                        </span>
                                        <span
                                            class="font-mono text-amber-700 font-semibold">+{{ number_format($campaign->delivery_fee ?? 0, 0, ',', '.') }}
                                            ₫</span>
                                    </div>
                                    @if ((int) ($campaign->discount ?? 0) > 0)
                                        <div class="flex justify-between items-center text-outline">
                                            <span class="flex items-center gap-1">
                                                <span>{{ __('admin.discount_input') }}:</span>
                                            </span>
                                            <span
                                                class="font-mono text-emerald-700 font-semibold">-{{ number_format($campaign->discount ?? 0, 0, ',', '.') }}
                                                ₫</span>
                                        </div>
                                    @endif
                                    <div
                                        class="flex justify-between items-center pt-2 font-bold text-on-surface border-t border-outline-variant/40">
                                        <span class="text-sm">{{ __('admin.gross_total') }}:</span>
                                        <span class="font-mono text-base text-primary font-bold">
                                            {{ number_format(max(0, ($grossSubtotal ?? 0) + ($campaign->delivery_fee ?? 0) - ($campaign->discount ?? 0)), 0, ',', '.') }}
                                            ₫
                                        </span>
                                    </div>
                                </div>

                                <!-- Subsidy section (if applicable) -->
                                @if (
                                    $sponsorSubsidy > 0 ||
                                        !empty($campaign->sponsor_name) ||
                                        $campaign->sponsor_type === 'full' ||
                                        (!empty($campaign->sponsor_allocations) && count($campaign->sponsor_allocations) > 0))
                                    <div
                                        class="space-y-2.5 bg-surface-container-low p-3.5 rounded-lg border border-outline-variant/60">
                                        <div class="flex justify-between items-center font-bold text-primary">
                                            <span class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px]">redeem</span>
                                                <span>{{ __('admin.multi_sponsor_subsidy') }}</span>
                                            </span>
                                            <span
                                                class="font-mono text-emerald-700">-{{ number_format($sponsorSubsidy ?? 0, 0, ',', '.') }}
                                                ₫</span>
                                        </div>
                                        @if (!empty($sponsorsList) && $sponsorsList->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                                @foreach ($sponsorsList as $sp)
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs font-medium shadow-2xs">
                                                        <span
                                                            class="material-symbols-outlined text-[14px] text-emerald-600">volunteer_activism</span>
                                                        <span
                                                            class="font-bold text-emerald-950">{{ $sp['name'] }}</span>
                                                        @if (($sp['percentage'] ?? 0) > 0)
                                                            <span
                                                                class="font-mono bg-emerald-600 text-white text-[10px] px-1.5 py-0.2 rounded-full font-bold">
                                                                {{ $sp['percentage'] }}%
                                                            </span>
                                                        @endif
                                                        @if (($sp['amount'] ?? 0) > 0)
                                                            <span
                                                                class="font-mono text-emerald-800 text-[11px] font-semibold">
                                                                {{ number_format($sp['amount'], 0, ',', '.') }} ₫
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @elseif(!empty($campaign->sponsor_name))
                                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs font-medium shadow-2xs">
                                                    <span
                                                        class="material-symbols-outlined text-[14px] text-emerald-600">volunteer_activism</span>
                                                    <span
                                                        class="font-bold text-emerald-950">{{ $campaign->sponsor_name }}</span>
                                                    <span class="font-mono text-emerald-800 text-[11px] font-semibold">
                                                        {{ number_format($sponsorSubsidy ?? 0, 0, ',', '.') }} ₫
                                                    </span>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN 2: DELIVERY STATUS & NOTIFICATION -->
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs overflow-hidden flex flex-col justify-between">
                        <div>
                            <div
                                class="p-4 border-b border-outline-variant/60 bg-surface-container-low flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="material-symbols-outlined text-primary text-[20px]">local_shipping</span>
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.delivery_status_title') }}</h3>
                                </div>
                                <span
                                    class="text-xs bg-primary/10 text-primary px-2.5 py-0.5 rounded-full font-mono font-semibold">
                                    {{ $campaign->status instanceof \App\Enums\CampaignStatus ? $campaign->status->label() : __('admin.status_' . ($campaign->status?->value ?? (string) $campaign->status)) }}
                                </span>
                            </div>

                            <div class="p-5 space-y-4 text-xs">
                                <p class="text-on-surface-variant leading-relaxed">
                                    {{ __('admin.delivery_status_desc') }}
                                </p>

                                <div class="grid grid-cols-2 gap-3 pt-2">
                                    <div
                                        class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/60">
                                        <span
                                            class="text-[11px] text-outline block">{{ __('admin.delivering_orders_count') }}</span>
                                        <span class="text-base font-bold text-primary font-mono mt-0.5 block">
                                            {{ $orders->where('status', \App\Enums\OrderStatus::Delivering)->count() }}
                                            /
                                            {{ $orders->count() }}
                                        </span>
                                    </div>
                                    <div
                                        class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/60">
                                        <span
                                            class="text-[11px] text-outline block">{{ __('admin.expected_delivery_time') }}</span>
                                        <span class="text-base font-bold text-on-surface font-mono mt-0.5 block">
                                            {{ $campaign->deadline?->format('H:i') ?? '--:--' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-surface-container-low border-t border-outline-variant/60">
                            <button type="button" @click="confirmDeliveryModalOpen = true"
                                :disabled="isDeliveringLoading || {{ $isCampaignClosed ? 'true' : 'false' }}"
                                class="w-full py-2.5 px-4 bg-primary hover:bg-primary-container text-on-primary rounded text-xs font-semibold flex items-center justify-center gap-2 shadow-sm transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primary">
                                <span class="material-symbols-outlined text-[18px]">delivery_dining</span>
                                <span>{{ __('admin.mark_items_delivered_btn') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: TABBED INTERFACE FOR ORDER AGGREGATION, INDIVIDUAL ORDERS, DEPARTMENTS & SETTLEMENT LEDGER -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs overflow-hidden"
                    x-data="{ activeTab: 'aggregated' }">
                    <!-- Tabs Navigation Header -->
                    <div
                        class="border-b border-outline-variant/60 bg-surface-container-low px-4 pt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2 overflow-x-auto">
                            @php
                                $aggregatedCount = count($aggregatedItems ?? []);
                                $ordersCount = $orders->count();
                                $departmentsCount = count($departmentGroups ?? []);
                                $debtsCount = $campaign->debts->count();
                                $declinedCount = (int) ($declinedUsersCount ?? count($declinedUsers ?? []));
                                $unresponsiveCount = (int) ($pendingUsersCount ?? count($unresponsiveUsers ?? []));
                            @endphp

                            <!-- TAB 1: MÓN GỘP -->
                            <button type="button" @click="activeTab = 'aggregated'"
                                :class="activeTab === 'aggregated' ?
                                    'border-primary text-primary font-bold bg-surface-container-lowest' :
                                    'border-transparent text-outline hover:text-on-surface'"
                                class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                <span>{{ __('admin.aggregated_items_list') }}</span>
                                @if ($aggregatedCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500 text-white">
                                        {{ $aggregatedCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- TAB 2: DANH SÁCH ĐƠN HÀNG -->
                            <button type="button" @click="activeTab = 'orders'"
                                :class="activeTab === 'orders' ?
                                    'border-primary text-primary font-bold bg-surface-container-lowest' :
                                    'border-transparent text-outline hover:text-on-surface'"
                                class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">list_alt</span>
                                <span>{{ __('admin.orders_list_tab') }}</span>
                                @if ($ordersCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500 text-white">
                                        {{ $ordersCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- TAB 3: MÓN THEO PHÒNG BAN -->
                            <button type="button" @click="activeTab = 'departments'"
                                :class="activeTab === 'departments' ?
                                    'border-primary text-primary font-bold bg-surface-container-lowest' :
                                    'border-transparent text-outline hover:text-on-surface'"
                                class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">corporate_fare</span>
                                <span>{{ __('admin.department_items_tab') }}</span>
                                @if ($departmentsCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500 text-white">
                                        {{ $departmentsCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- TAB 4: SỔ NỢ CÁ NHÂN -->
                            <button type="button" @click="activeTab = 'ledger'"
                                :class="activeTab === 'ledger' ?
                                    'border-primary text-primary font-bold bg-surface-container-lowest' :
                                    'border-transparent text-outline hover:text-on-surface'"
                                class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                                <span>{{ __('admin.participant_settlement_ledger') }}</span>
                                @if ($debtsCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500 text-white">
                                        {{ $debtsCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- TAB 5: DANH SÁCH USER KHÔNG THAM GIA -->
                            <button type="button" @click="activeTab = 'declined'"
                                :class="activeTab === 'declined' ?
                                    'border-primary text-primary font-bold bg-surface-container-lowest' :
                                    'border-transparent text-outline hover:text-on-surface'"
                                class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">block</span>
                                <span>{{ __('admin.declined_users_tab') }}</span>
                                @if ($declinedCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500 text-white">
                                        {{ $declinedCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- TAB 6: DANH SÁCH USER KHÔNG PHẢN HỒI -->
                            <button type="button" @click="activeTab = 'unresponsive'"
                                :class="activeTab === 'unresponsive' ?
                                    'border-primary text-primary font-bold bg-surface-container-lowest' :
                                    'border-transparent text-outline hover:text-on-surface'"
                                class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">hourglass_empty</span>
                                <span>{{ __('admin.unresponsive_users_tab') }}</span>
                                @if ($unresponsiveCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500 text-white">
                                        {{ $unresponsiveCount }}
                                    </span>
                                @endif
                            </button>
                        </div>
                    </div>

                    <!-- TAB 1: AGGREGATED ORDER LIST -->
                    <div x-show="activeTab === 'aggregated'" class="p-4 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.aggregated_items_for_store') }}
                                        {{ $campaign->restaurant }}
                                    </h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-primary border border-emerald-200 font-mono text-xs font-semibold">
                                        {{ count($aggregatedItems ?? []) }} {{ __('admin.item_groups') }}
                                    </span>
                                </div>
                                <p class="text-xs text-outline">{{ __('admin.aggregated_items_desc') }}</p>
                            </div>
                            @if ($aggregatedCount > 0)
                                <a download data-download-button
                                    href="{{ route('admin.campaigns.export-detail', [$room, $campaign, 'aggregated']) }}"
                                    @click="downloadExport($event, 'aggregated')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors no-underline"><span
                                        data-download-icon
                                        class="material-symbols-outlined text-[16px]">download</span><span
                                        data-download-label>{{ __('admin.download') }}</span></a>
                                </div>
                            @endif
                        </div>

                        <div class="overflow-x-auto w-full">
                            <table class="w-full min-w-full text-left border-collapse text-xs table-fixed">
                                <thead>
                                    <tr
                                        class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                                        <th class="px-4 py-2 w-16 text-left">{{ __('admin.order_no') }}</th>
                                        <th class="px-4 py-2 text-left">{{ __('admin.item_name_customization') }}
                                        </th>
                                        <th class="px-4 py-2 w-32 text-center">{{ __('admin.quantity') }}</th>
                                        <th class="px-4 py-2 w-36 text-right">{{ __('admin.unit_price') }}</th>
                                        <th class="px-4 py-2 w-36 text-right">{{ __('admin.total_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($aggregatedItems as $index => $item)
                                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                                            <td class="px-4 py-3 w-16 text-left font-mono text-outline">
                                                {{ $index + 1 }}</td>
                                            <td class="px-4 py-3 text-left">
                                                <div class="font-semibold text-on-surface">{{ $item['name'] }}
                                                    {{ $item['size'] ? '(' . $item['size'] . ')' : '' }}
                                                </div>
                                                @if (!empty($item['notes']) && $item['notes']->isNotEmpty())
                                                    <div class="text-[11px] text-outline mt-0.5">
                                                        {{ __('admin.notes') }}:
                                                        {{ $item['notes']->unique()->join(' • ') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 w-32 text-center">
                                                <span
                                                    class="inline-block px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-primary font-mono font-bold rounded">
                                                    {{ $item['quantity'] }} {{ __('admin.portions') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 w-36 text-right font-mono text-outline">
                                                {{ number_format($item['unit_price'], 0, ',', '.') }} ₫
                                            </td>
                                            <td class="px-4 py-3 w-36 text-right font-mono font-bold text-on-surface">
                                                {{ number_format($item['total_amount'], 0, ',', '.') }} ₫
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-12 text-center text-outline">
                                                <div class="flex flex-col items-center justify-center gap-2 py-2">
                                                    <span
                                                        class="material-symbols-outlined text-4xl text-outline-variant">receipt_long</span>
                                                    <p class="font-medium text-xs text-outline">
                                                        {{ __('admin.no_items_ordered') }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: INDIVIDUAL ORDERS LIST -->
                    <div x-show="activeTab === 'orders'" class="p-4 space-y-3" style="display: none;">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-on-surface">{{ __('admin.orders_list_tab') }}
                                    </h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-primary border border-emerald-200 font-mono text-xs font-semibold">
                                        {{ __('admin.orders_count_badge', ['count' => $orders->count()]) }}
                                    </span>
                                </div>
                                <p class="text-xs text-outline">{{ __('admin.orders_list_desc') }}</p>
                            </div>
                            @if ($ordersCount > 0)
                                <div class="flex items-center gap-2">
                                    @if ($isCampaignLive)
                                        <button type="button" @click="confirmAllOrders()" :disabled="isConfirmingAllOrders"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-primary bg-primary px-3 py-2 text-xs font-semibold text-on-primary hover:opacity-90 transition-colors disabled:cursor-not-allowed disabled:opacity-60">
                                            <span class="material-symbols-outlined text-[16px]"
                                                :class="{ 'animate-spin': isConfirmingAllOrders }"
                                                x-text="isConfirmingAllOrders ? 'progress_activity' : 'done_all'">done_all</span>
                                            <span x-text="isConfirmingAllOrders ? '{{ __('admin.processing') }}...' : '{{ __('admin.confirm_all_orders') }}'">{{ __('admin.confirm_all_orders') }}</span>
                                        </button>
                                    @endif
                                <a download data-download-button
                                    href="{{ route('admin.campaigns.export-detail', [$room, $campaign, 'orders']) }}"
                                    @click="downloadExport($event, 'orders')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors no-underline"><span
                                        data-download-icon
                                        class="material-symbols-outlined text-[16px]">download</span><span
                                        data-download-label>{{ __('admin.download') }}</span></a>
                            @endif
                        </div>

                        <div class="overflow-x-auto w-full">
                            <table class="w-full min-w-full text-left border-collapse text-xs table-fixed">
                                <thead>
                                    <tr
                                        class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                                        <th class="px-4 py-2 w-14 text-left">{{ __('admin.order_no') }}</th>
                                        <th class="px-4 py-2 w-48 text-left">{{ __('admin.member') }}</th>
                                        <th class="px-4 py-2 text-left">{{ __('admin.order_items_detail') }}</th>
                                        <th class="px-4 py-2 w-32 text-right">{{ __('admin.payable') }}</th>
                                        <th class="px-4 py-2 w-36 text-center">
                                            {{ __('admin.order_confirmed_switch') }}</th>
                                        <th class="px-4 py-2 w-28 text-center">{{ __('admin.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($orders as $index => $order)
                                        @php
                                            $roomUser = $order->roomUser;
                                            $globalUser = $roomUser?->globalUser;
                                            $location = $globalUser?->desk_location;
                                        @endphp
                                        <tr class="hover:bg-surface-container-low/50 transition-colors align-top">
                                            <td class="px-4 py-3 w-14 text-left font-mono text-outline">
                                                {{ $index + 1 }}</td>
                                            <td class="px-4 py-3 w-48 text-left">
                                                <div>
                                                    <div class="font-semibold text-on-surface">
                                                        {{ $roomUser?->display_name ?? __('admin.member') }}
                                                        @if ($order->parent?->roomUser)
                                                            <span class="group/proxy relative inline-flex align-middle ml-1" aria-describedby="proxy-order-info-tooltip-{{ $order->id }}">
                                                                <span class="material-symbols-outlined text-[15px] text-primary cursor-help">info</span>
                                                                <span id="proxy-order-info-tooltip-{{ $order->id }}" role="tooltip"
                                                                    class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/proxy:opacity-100">{{ __('admin.proxy_order_info', ['name' => $order->parent->roomUser?->display_name ?? __('admin.member'), 'email' => $order->parent->roomUser?->globalUser?->email ?? '']) }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div
                                                        class="text-[10px] font-mono text-outline flex items-center gap-1.5 flex-wrap mt-0.5">
                                                        <span class="inline-flex items-center gap-1">
                                                            <span>{{ $order->code }}</span>
                                                            <button type="button" @click="copyOrderCode('{{ addslashes($order->code) }}', $event)" data-copy-order-code="{{ $order->code }}"
                                                                class="group/copy relative inline-flex items-center justify-center text-outline hover:text-primary transition-colors"
                                                                aria-describedby="copy-order-code-tooltip-{{ $order->id }}"
                                                                aria-label="{{ __('admin.copy_order_code') }}">
                                                                <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                                                <span id="copy-order-code-tooltip-{{ $order->id }}" role="tooltip"
                                                                    class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/copy:opacity-100 group-focus-visible/copy:opacity-100">{{ __('admin.copy_order_code') }}</span>
                                                            </button>
                                                        </span>
                                                        @if ($location)
                                                            <span
                                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant text-[9px] font-medium border border-outline-variant/60">
                                                                <span
                                                                    class="material-symbols-outlined text-[11px]">location_on</span>
                                                                {{ $location }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-left">
                                                <div class="space-y-1.5">
                                                    @foreach ($order->items as $item)
                                                        <div class="text-xs">
                                                            <span
                                                                class="font-semibold text-on-surface">{{ $item->item_name }}</span>
                                                            @if ($item->size_name)
                                                                <span
                                                                    class="text-outline">({{ $item->size_name }})</span>
                                                            @endif
                                                            <span
                                                                class="text-primary font-mono font-bold">x{{ $item->quantity }}</span>
                                                            <span
                                                                class="text-outline font-mono text-[11px]">({{ number_format($item->unit_price, 0, ',', '.') }}₫)</span>
                                                            @if (!empty($item->note))
                                                                <div
                                                                    class="text-[11px] text-outline italic pl-2 border-l-2 border-outline-variant/60 mt-0.5">
                                                                    📝 {{ $item->note }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                    @if (!empty($order->note))
                                                        <div
                                                            class="text-[11px] text-amber-700 bg-amber-50 p-1.5 rounded border border-amber-200 mt-1">
                                                            <span
                                                                class="font-semibold">{{ __('admin.order_note') }}:</span>
                                                            {{ $order->note }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 w-32 text-right font-mono font-bold text-on-surface">
                                                {{ number_format($order->final_amount, 0, ',', '.') }} ₫
                                            </td>
                                            <td class="px-4 py-3 w-36 text-center">
                                                <div class="flex items-center justify-center min-h-[24px]">
                                                    <!-- Loading Spinner -->
                                                    <div x-show="isUpdatingOrder['{{ $order->id }}']"
                                                        class="flex items-center justify-center gap-1 text-primary text-[11px] font-mono">
                                                        <span
                                                            class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                                                    </div>
                                                    <!-- Switch -->
                                                    <label x-show="!isUpdatingOrder['{{ $order->id }}']"
                                                        class="relative inline-flex items-center cursor-pointer select-none"
                                                        :title="orderStatus['{{ $order->id }}'] === 'confirmed' ?
                                                            '{{ __('admin.status_confirmed') }}' :
                                                            '{{ __('admin.status_submitted') }}'">
                                                        <input type="checkbox"
                                                            :checked="orderStatus['{{ $order->id }}'] === 'confirmed'"
                                                            :disabled="isUpdatingOrder['{{ $order->id }}'] || !['submitted',
                                                                'confirmed'
                                                            ].includes(orderStatus['{{ $order->id }}'])"
                                                            @change="toggleOrderConfirmation({{ $order->id }})"
                                                            class="sr-only peer">
                                                        <div
                                                            class="w-8 h-4 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-primary peer-disabled:opacity-40 peer-disabled:cursor-not-allowed">
                                                        </div>
                                                    </label>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 w-28 text-center">
                                                <span class="group/detail relative inline-flex">
                                                    <button type="button" @click="openOrderDetail({{ $order->id }})"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary border border-outline-variant/60 transition-colors cursor-pointer"
                                                    aria-label="{{ __('admin.view_order_detail') }}">
                                                    <span
                                                        class="material-symbols-outlined text-[18px]">visibility</span>
                                                    </button>
                                                    <span role="tooltip"
                                                        class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-lg transition-opacity group-hover/detail:opacity-100 group-focus-within/detail:opacity-100">{{ __('admin.view_order_detail') }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-12 text-center text-outline">
                                                <div class="flex flex-col items-center justify-center gap-2 py-2">
                                                    <span
                                                        class="material-symbols-outlined text-4xl text-outline-variant">shopping_cart</span>
                                                    <p class="font-medium text-xs text-outline">
                                                        {{ __('admin.no_orders_in_campaign') }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: ITEMS GROUPED BY DEPARTMENT -->
                    <div x-show="$store.campaignTabs.activeTab === 'departments'" class="p-4 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.department_items_tab') }}</h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-primary border border-emerald-200 font-mono text-xs font-semibold">
                                        {{ __('admin.department_count_badge', ['count' => count($departmentGroups ?? [])]) }}
                                    </span>
                                </div>
                                <p class="text-xs text-outline">{{ __('admin.department_summary') }}</p>
                            </div>
                            @if ($departmentsCount > 0)
                                <a download data-download-button
                                    href="{{ route('admin.campaigns.export-detail', [$room, $campaign, 'departments']) }}"
                                    @click="downloadExport($event, 'departments')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors no-underline"><span
                                        data-download-icon
                                        class="material-symbols-outlined text-[16px]">download</span><span
                                        data-download-label>{{ __('admin.download') }}</span></a>
                            @endif
                        </div>

                        <div class="space-y-4">
                            @forelse($departmentGroups as $dept)
                                <div
                                    class="border border-outline-variant rounded-xl overflow-hidden bg-surface-container-lowest shadow-xs">
                                    <div
                                        class="p-3.5 bg-surface-container-low border-b border-outline-variant/60 flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="material-symbols-outlined text-primary text-[20px]">corporate_fare</span>
                                            <h4 class="text-xs font-bold text-on-surface">{{ $dept['department'] }}
                                            </h4>
                                        </div>
                                        <div class="flex items-center gap-2 font-mono text-[11px]">
                                            <span
                                                class="px-2.5 py-0.5 rounded bg-surface-container text-on-surface-variant border border-outline-variant">
                                                {{ __('admin.department_members_count', ['count' => $dept['members']->count()]) }}
                                            </span>
                                            <span
                                                class="px-2.5 py-0.5 rounded bg-emerald-50 text-primary border border-emerald-200 font-semibold">
                                                {{ __('admin.department_orders_count', ['count' => $dept['total_quantity']]) }}
                                            </span>
                                            <span
                                                class="px-2.5 py-0.5 rounded bg-surface-container-high text-on-surface font-bold">
                                                {{ number_format($dept['total_amount'], 0, ',', '.') }} ₫
                                            </span>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse text-xs table-fixed">
                                            <thead>
                                                <tr
                                                    class="h-8 bg-surface-container-lowest border-b border-outline-variant/60 text-outline uppercase font-mono tracking-wider text-[11px]">
                                                    <th class="px-4 py-2 w-14 text-left">{{ __('admin.order_no') }}
                                                    </th>
                                                    <th class="px-4 py-2 text-left">
                                                        {{ __('admin.item_name_customization') }}</th>
                                                    <th class="px-4 py-2 w-48 text-left">{{ __('admin.ordered_by') }}
                                                    </th>
                                                    <th class="px-4 py-2 w-28 text-center">{{ __('admin.quantity') }}
                                                    </th>
                                                    <th class="px-4 py-2 w-32 text-right">
                                                        {{ __('admin.total_amount') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-outline-variant/40">
                                                @foreach ($dept['items'] as $index => $item)
                                                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                                                        <td class="px-4 py-2.5 w-14 text-left font-mono text-outline">
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td class="px-4 py-2.5 text-left">
                                                            <div class="font-semibold text-on-surface">
                                                                {{ $item['name'] }}
                                                                {{ $item['size'] ? '(' . $item['size'] . ')' : '' }}
                                                            </div>
                                                            @if (!empty($item['notes']) && $item['notes']->isNotEmpty())
                                                                <div class="text-[11px] text-outline mt-0.5">
                                                                    {{ __('admin.notes') }}:
                                                                    {{ $item['notes']->unique()->join(' • ') }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2.5 w-48 text-left text-on-surface-variant">
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach ($item['members'] as $memberEntry)
                                                                    <span
                                                                        class="inline-block px-2 py-0.5 bg-surface-container-low border border-outline-variant/60 rounded text-[11px] text-on-surface">
                                                                        {{ $memberEntry }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2.5 w-28 text-center">
                                                            <span
                                                                class="inline-block px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-primary font-mono font-bold rounded">
                                                                {{ $item['quantity'] }} {{ __('admin.portions') }}
                                                            </span>
                                                        </td>
                                                        <td
                                                            class="px-4 py-2.5 w-32 text-right font-mono font-bold text-on-surface">
                                                            {{ number_format($item['total_amount'], 0, ',', '.') }} ₫
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="p-12 text-center text-outline border border-outline-variant rounded-xl bg-surface-container-lowest flex flex-col items-center justify-center gap-2">
                                    <span
                                        class="material-symbols-outlined text-4xl text-outline-variant">corporate_fare</span>
                                    <p class="font-medium text-xs text-outline">
                                        {{ __('admin.no_orders_in_campaign') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- TAB 4: PARTICIPANT SETTLEMENT LEDGER -->
                    <div x-show="activeTab === 'ledger'" class="p-4 space-y-3" style="display: none;">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.participant_settlement_ledger') }}
                                    </h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-primary border border-emerald-200 font-mono text-xs font-semibold">
                                        {{ __('admin.orders_count_badge', ['count' => $orders->count()]) }}
                                    </span>
                                </div>
                                <p class="text-xs text-outline">{{ __('admin.order_allocation_debt_desc') }}</p>
                            </div>
                            @if ($debtsCount > 0)
                                <a download data-download-button
                                    href="{{ route('admin.campaigns.export-detail', [$room, $campaign, 'debts']) }}"
                                    @click="downloadExport($event, 'debts')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors no-underline"><span
                                        data-download-icon
                                        class="material-symbols-outlined text-[16px]">download</span><span
                                        data-download-label>{{ __('admin.download') }}</span></a>
                            @endif
                        </div>

                        @if ($isCampaignClosed || $campaign->debts->isNotEmpty())
                            <div class="overflow-x-auto w-full">
                                <table class="w-full min-w-full text-left border-collapse text-xs table-fixed">
                                    <thead>
                                        <tr
                                            class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                                            <th class="px-4 py-2 w-14 text-left">{{ __('admin.order_no') }}</th>
                                            <th class="px-4 py-2 w-48 text-left">{{ __('admin.member') }}</th>
                                            <th class="px-4 py-2 w-32 text-right">{{ __('admin.amount') }}</th>
                                            <th class="px-4 py-2 text-left">{{ __('admin.request_content') }}</th>
                                            <th class="px-4 py-2 w-44 text-center">{{ __('admin.status') }}</th>
                                            <th class="px-4 py-2 w-28 text-center">{{ __('admin.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/40">
                                        @forelse($campaign->debts as $index => $debt)
                                            @php
                                                $roomUser = $debt->roomUser;
                                                $globalUser = $roomUser?->globalUser;
                                                $location = $globalUser?->desk_location;
                                                $transferContent = $debt->note ?: $debt->code;
                                            @endphp
                                            <tr class="hover:bg-surface-container-low/50 transition-colors align-top">
                                                <td class="px-4 py-3 w-14 text-left font-mono text-outline">
                                                    {{ $loop->iteration }}</td>
                                                <td class="px-4 py-3 w-48 text-left">
                                                    <div>
                                                        <div class="font-semibold text-on-surface">
                                                            {{ $roomUser?->display_name ?? __('admin.member') }}
                                                        </div>
                                                        <div
                                                            class="text-[10px] font-mono text-outline flex items-center gap-1.5 flex-wrap mt-0.5">
                                                            @if ($roomUser?->user_code)
                                                                <span>#{{ $roomUser->user_code }}</span>
                                                            @endif
                                                            @if ($location)
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant text-[9px] font-medium border border-outline-variant/60">
                                                                    <span
                                                                        class="material-symbols-outlined text-[11px]">location_on</span>
                                                                    {{ $location }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 w-32 text-right">
                                                    <div class="font-bold font-mono text-on-surface text-xs">
                                                        {{ number_format($debt->original_amount, 0, ',', '.') }} ₫
                                                    </div>
                                                    <div class="text-[10px] font-mono mt-0.5"
                                                        :class="debtsStatus['{{ $debt->id }}'] === 'paid' ?
                                                            'text-emerald-700' : 'text-amber-700'"
                                                        x-text="debtsStatus['{{ $debt->id }}'] === 'paid' ? '{{ __('admin.filter_debt_paid') }}' : ('{{ __('admin.remaining_debt') }}: ' + formatCurrency({{ (int) $debt->remaining_amount }}))">
                                                        {{ $debt->status?->value === 'paid' ? __('admin.filter_debt_paid') : __('admin.remaining_debt') . ': ' . number_format($debt->remaining_amount, 0, ',', '.') . ' ₫' }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-left">
                                                    <span
                                                        class="font-mono text-xs text-primary font-semibold block">{{ $transferContent }}</span>
                                                    <span
                                                        class="text-[10px] font-mono text-outline">#{{ $debt->code }}</span>
                                                </td>
                                                <td class="px-4 py-3 w-44 text-center">
                                                    <div class="flex items-center justify-center">
                                                        <select :value="debtsStatus['{{ $debt->id }}']"
                                                            :disabled="isUpdatingDebt['{{ $debt->id }}']"
                                                            @change="updateDebtStatus({{ $debt->id }}, $event.target.value)"
                                                            class="h-8 px-2 py-1 text-xs font-semibold rounded-lg border transition-colors outline-hidden cursor-pointer"
                                                            :class="{
                                                                'bg-emerald-50 text-emerald-800 border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500': debtsStatus[
                                                                    '{{ $debt->id }}'] === 'paid',
                                                                'bg-amber-50 text-amber-900 border-amber-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 font-bold': debtsStatus[
                                                                        '{{ $debt->id }}'] === 'unpaid' ||
                                                                    debtsStatus[
                                                                        '{{ $debt->id }}'] === 'pending',
                                                                'bg-blue-50 text-blue-800 border-blue-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500': debtsStatus[
                                                                    '{{ $debt->id }}'] === 'partial',
                                                                'bg-surface-container text-outline border-outline-variant': debtsStatus[
                                                                    '{{ $debt->id }}'] === 'waived'
                                                            }">
                                                            <option value="unpaid">
                                                                {{ __('admin.filter_debt_unpaid') }}</option>
                                                            <option value="paid">{{ __('admin.filter_debt_paid') }}
                                                            </option>
                                                            <option value="waived">{{ __('admin.status_waived') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 w-28 text-center">
                                                    <button type="button"
                                                        @click="openDebtDetail({{ $debt->id }})"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-surface-container hover:bg-surface-container-high text-primary border border-outline-variant/60 text-xs font-semibold transition-colors cursor-pointer"
                                                        title="{{ __('admin.view_order_detail') }}">
                                                        <span
                                                            class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>{{ __('admin.view_order_detail') }}</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="py-12 text-center text-outline">
                                                    <div class="flex flex-col items-center justify-center gap-2 py-2">
                                                        <span
                                                            class="material-symbols-outlined text-4xl text-outline-variant">account_balance_wallet</span>
                                                        <p class="font-medium text-xs text-outline">
                                                            {{ __('admin.no_debts_recorded') }}</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-8 bg-amber-50/70 border border-amber-200 rounded-xl text-center space-y-3">
                                <div
                                    class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto">
                                    <span class="material-symbols-outlined text-[26px]">lock_clock</span>
                                </div>
                                <div class="max-w-lg mx-auto space-y-1">
                                    <h4 class="text-sm font-bold text-amber-950">
                                        {{ __('admin.debt_ledger_not_closed_title') }}</h4>
                                    <p class="text-xs text-amber-800 leading-relaxed">
                                        {{ __('admin.debt_ledger_closed_only_notice') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- TAB 5: DECLINED USERS LIST -->
                    <div x-show="activeTab === 'declined'" class="p-4 space-y-3" style="display: none;">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.declined_users_tab') }}
                                    </h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-mono text-xs font-semibold">
                                        {{ $declinedUsersCount ?? count($declinedUsers ?? []) }}
                                        {{ __('admin.member') }}
                                    </span>
                                </div>
                                <p class="text-xs text-outline">{{ __('admin.declined_users_desc') }}</p>
                            </div>
                            @if ($declinedCount > 0)
                                <a download data-download-button
                                    href="{{ route('admin.campaigns.export-detail', [$room, $campaign, 'declined']) }}"
                                    @click="downloadExport($event, 'declined')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors no-underline"><span
                                        data-download-icon
                                        class="material-symbols-outlined text-[16px]">download</span><span
                                        data-download-label>{{ __('admin.download') }}</span></a>
                            @endif
                        </div>

                        <div class="overflow-x-auto w-full">
                            <table class="w-full min-w-full text-left border-collapse text-xs table-fixed">
                                <thead>
                                    <tr
                                        class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                                        <th class="px-4 py-2 w-16 text-left">{{ __('admin.order_no') }}</th>
                                        <th class="px-4 py-2 text-left">{{ __('admin.member_full_name') }}</th>
                                        <th class="px-4 py-2 w-64 text-left">{{ __('admin.member_department') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($declinedUsers as $index => $u)
                                        @php
                                            $globalUser = $u->globalUser;
                                            $dept =
                                                trim((string) ($globalUser?->desk_location ?? '')) ?:
                                                __('admin.unassigned_department');
                                            $name = $globalUser?->name ?? $u->display_name;
                                        @endphp
                                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                                            <td class="px-4 py-3 w-16 text-left font-mono text-outline">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-4 py-3 text-left">
                                                <div class="flex items-center gap-2.5">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-xs shrink-0 border border-rose-200">
                                                        {{ mb_substr($name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-on-surface">
                                                            {{ $name }}</div>
                                                        <div
                                                            class="text-[10px] font-mono text-outline flex items-center gap-1.5 mt-0.5">
                                                            <span>{{ $globalUser->email }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 w-64 text-left">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-surface-container text-on-surface-variant font-medium text-xs border border-outline-variant/60">
                                                    <span class="material-symbols-outlined text-[14px] text-outline">corporate_fare</span>
                                                    <span>{{ $dept }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-12 text-center text-outline">
                                                <div class="flex flex-col items-center justify-center gap-2 py-2">
                                                    <span class="material-symbols-outlined text-4xl text-outline-variant">check_circle</span>
                                                    <p class="font-medium text-xs text-outline">{{ __('admin.no_declined_users') }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 6: UNRESPONSIVE USERS LIST -->
                    <div x-show="activeTab === 'unresponsive'" class="p-4 space-y-3" style="display: none;">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.unresponsive_users_tab') }}
                                    </h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-mono text-xs font-semibold">
                                        {{ $pendingUsersCount ?? count($unresponsiveUsers ?? []) }}
                                        {{ __('admin.member') }}
                                    </span>
                                </div>
                                <p class="text-xs text-outline">{{ __('admin.unresponsive_users_desc') }}</p>
                            </div>
                            @if ($unresponsiveCount > 0)
                                <a download data-download-button
                                    href="{{ route('admin.campaigns.export-detail', [$room, $campaign, 'unresponsive']) }}"
                                    @click="downloadExport($event, 'unresponsive')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors no-underline"><span
                                        data-download-icon
                                        class="material-symbols-outlined text-[16px]">download</span><span
                                        data-download-label>{{ __('admin.download') }}</span></a>
                            @endif
                        </div>

                        <div class="overflow-x-auto w-full">
                            <table class="w-full min-w-full text-left border-collapse text-xs table-fixed">
                                <thead>
                                    <tr
                                        class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                                        <th class="px-4 py-2 w-16 text-left">{{ __('admin.order_no') }}</th>
                                        <th class="px-4 py-2 text-left">{{ __('admin.member_full_name') }}</th>
                                        <th class="px-4 py-2 w-64 text-left">{{ __('admin.member_department') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($unresponsiveUsers as $index => $u)
                                        @php
                                            $globalUser = $u->globalUser;
                                            $dept =
                                                trim((string) ($globalUser?->desk_location ?? '')) ?:
                                                __('admin.unassigned_department');
                                            $name = $globalUser?->name ?? $u->display_name;
                                        @endphp
                                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                                            <td class="px-4 py-3 w-16 text-left font-mono text-outline">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-4 py-3 text-left">
                                                <div class="flex items-center gap-2.5">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-amber-50 text-amber-800 flex items-center justify-center font-bold text-xs shrink-0 border border-amber-200">
                                                        {{ mb_substr($name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-on-surface">
                                                            {{ $name }}</div>
                                                        <div
                                                            class="text-[10px] font-mono text-outline flex items-center gap-1.5 mt-0.5">
                                                            <span>{{ $globalUser->email }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 w-64 text-left">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-surface-container text-on-surface-variant font-medium text-xs border border-outline-variant/60">
                                                    <span class="material-symbols-outlined text-[14px] text-outline">corporate_fare</span>
                                                    <span>{{ $dept }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-12 text-center text-outline">
                                                <div class="flex flex-col items-center justify-center gap-2 py-2">
                                                    <span class="material-symbols-outlined text-4xl text-outline-variant">task_alt</span>
                                                    <p class="font-medium text-xs text-outline">{{ __('admin.no_unresponsive_users') }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <!-- DRAFT STATUS NOTICE -->
                <div
                    class="bg-surface-container-lowest border border-dashed border-outline-variant rounded-xl p-8 text-center space-y-3">
                    <div
                        class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                        <span class="material-symbols-outlined text-[26px]">edit_note</span>
                    </div>
                    <div class="max-w-md mx-auto space-y-1">
                        <h3 class="text-sm font-bold text-on-surface">{{ __('admin.draft_campaign_notice_title') }}
                        </h3>
                        <p class="text-xs text-outline leading-relaxed">{{ __('admin.draft_campaign_notice_desc') }}
                        </p>
                    </div>
                    <div class="pt-2 flex justify-center gap-2">
                        <a href="{{ route('admin.campaigns.edit', [$room, $campaign]) }}"
                            class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold flex items-center gap-1.5 transition-colors no-underline">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                            <span>{{ __('admin.edit_campaign') }}</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- MODAL: ADJUST FEES & DISCOUNT -->
            <div x-show="adjustFeeModalOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                style="display: none;">
                <div @click.outside="adjustFeeModalOpen = false"
                    class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                    <div
                        class="p-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">tune</span>
                            <h3 class="text-base font-bold text-on-surface">
                                {{ __('admin.adjust_fees_discount_title') }}
                            </h3>
                        </div>
                        <button type="button" @click="adjustFeeModalOpen = false"
                            class="text-outline hover:text-on-surface">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="saveAdjustments()" class="p-5 space-y-4">
                        <p class="text-xs text-outline leading-relaxed">
                            {{ __('admin.adjust_fees_discount_desc') }}
                        </p>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label
                                    class="block text-xs font-semibold text-on-surface">{{ __('admin.delivery_fee_input') }}</label>
                            </div>
                            <div class="relative">
                                <input type="text" inputmode="numeric" :value="formatInput(deliveryFee)"
                                    @keydown="filterNumberInput($event)"
                                    @input="deliveryFee = parseInput($event.target.value)"
                                    class="w-full h-10 px-3 pr-8 bg-surface border border-outline-variant rounded-lg text-sm font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-hidden"
                                    placeholder="0">
                                <span
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-outline">₫</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label
                                    class="block text-xs font-semibold text-on-surface">{{ __('admin.discount_input') }}</label>
                            </div>
                            <div class="relative">
                                <input type="text" inputmode="numeric" :value="formatInput(discount)"
                                    @keydown="filterNumberInput($event)"
                                    @input="discount = parseInput($event.target.value)"
                                    :class="grossSubtotal > 0 && Number(discount) > (grossSubtotal + Number(deliveryFee)) ?
                                        'border-rose-500 focus:border-rose-500 focus:ring-rose-500' :
                                        'border-outline-variant focus:border-primary focus:ring-primary'"
                                    class="w-full h-10 px-3 pr-8 bg-surface border rounded-lg text-sm font-mono outline-hidden"
                                    placeholder="0">
                                <span
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-outline">₫</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] pt-0.5">
                                <span class="text-outline"
                                    x-text="'{{ __('admin.max_discount_hint', ['amount' => '']) }}' + formatCurrency(grossSubtotal + Number(deliveryFee))"></span>
                                <span
                                    x-show="grossSubtotal > 0 && Number(discount) > (grossSubtotal + Number(deliveryFee))"
                                    class="text-rose-600 font-medium">
                                    {{ __('admin.discount_cannot_exceed_subtotal_plus_fee_prompt') }} <span
                                        x-text="formatCurrency(grossSubtotal + Number(deliveryFee))"></span>
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                            <button type="button" @click="adjustFeeModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer">
                                {{ __('admin.cancel') }}
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs disabled:opacity-50">
                                <span class="material-symbols-outlined text-[16px]">save</span>
                                <span
                                    x-text="isSubmitting ? '{{ __('admin.processing') }}' : '{{ __('admin.save_adjustment') }}'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: CONFIRM CANCEL CAMPAIGN -->
            <div x-show="confirmCancelModalOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                style="display: none;">
                <div @click.outside="confirmCancelModalOpen = false"
                    class="bg-surface-container-lowest border border-error/30 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-error/20 flex items-center gap-3 bg-error-container/20">
                        <span
                            class="w-10 h-10 rounded-full bg-error/10 text-error flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[24px]">warning</span>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-on-surface">
                                {{ __('admin.confirm_cancel_campaign_title') }}
                            </h3>
                            <p class="text-xs text-error/80 font-medium">#{{ $campaign->code }} ·
                                {{ $campaign->name }}</p>
                        </div>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <p class="text-on-surface leading-relaxed">
                            {{ __('admin.confirm_cancel_campaign_desc') }}
                        </p>
                        <div
                            class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-lg text-outline">
                            {{ __('admin.confirm_delete_temporary_campaign_message') }}
                        </div>

                        <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                            <button type="button" @click="confirmCancelModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer">
                                {{ __('admin.cancel') }}
                            </button>
                            <button type="button" @click="cancelCampaign()" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-lg bg-error hover:bg-error/90 text-white text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs disabled:opacity-50">
                                <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                                <span
                                    x-text="isSubmitting ? '{{ __('admin.processing') }}' : '{{ __('admin.confirm_cancel_campaign_btn') }}'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: CLOSE CAMPAIGN SUMMARY & CONFIRM -->
            <div x-show="closeConfirmModalOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                style="display: none;">
                <div @click.outside="if (!isClosing) closeConfirmModalOpen = false"
                    class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
                    <div
                        class="p-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-9 h-9 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">lock_clock</span>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-on-surface">
                                    {{ __('admin.close_campaign_confirm_modal_title') }}
                                </h3>
                                <p class="text-xs text-outline font-mono">#{{ $campaign->code }} ·
                                    {{ $campaign->name }}</p>
                            </div>
                        </div>
                        <button type="button" @click="closeConfirmModalOpen = false" :disabled="isClosing"
                            class="text-outline hover:text-on-surface disabled:opacity-40 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <!-- Summary Card -->
                        <div
                            class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-outline-variant/40">
                                <span class="text-outline font-medium">{{ __('admin.restaurant_name') }}</span>
                                <span class="font-bold text-on-surface">{{ $campaign->restaurant }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 py-1">
                                <div class="flex flex-col">
                                    <span class="text-outline">{{ __('admin.ordered_members_label') }}</span>
                                    <span class="text-sm font-bold text-on-surface mt-0.5">{{ $orderedUsersCount }} /
                                        {{ $totalUsersCount }} {{ __('admin.member') }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-outline">{{ __('admin.total_items_ordered_label') }}</span>
                                    <span
                                        class="text-sm font-bold text-primary mt-0.5">{{ collect($aggregatedItems)->sum('quantity') }}
                                        {{ __('admin.portions') }}</span>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-outline-variant/40 space-y-1.5">
                                <div class="flex items-center justify-between text-outline">
                                    <span>{{ __('admin.gross_subtotal') }}</span>
                                    <span class="font-mono text-on-surface">{{ number_format($grossSubtotal) }}
                                        ₫</span>
                                </div>
                                @if (($campaign->delivery_fee ?? 0) > 0)
                                    <div class="flex items-center justify-between text-outline">
                                        <span>{{ __('admin.delivery_fee_input') }}</span>
                                        <span
                                            class="font-mono text-on-surface">+{{ number_format($campaign->delivery_fee) }}
                                            ₫</span>
                                    </div>
                                @endif
                                @if (($campaign->discount ?? 0) > 0)
                                    <div class="flex items-center justify-between text-emerald-600">
                                        <span>{{ __('admin.discount_input') }}</span>
                                        <span class="font-mono">-{{ number_format($campaign->discount) }} ₫</span>
                                    </div>
                                @endif
                                <div
                                    class="flex items-center justify-between font-bold text-sm text-on-surface pt-2 border-t border-outline-variant/40">
                                    <span>{{ __('admin.net_payable') }}</span>
                                    <span
                                        class="font-mono text-primary text-base">{{ number_format(max(0, $grossSubtotal + ($campaign->delivery_fee ?? 0) - ($campaign->discount ?? 0))) }}
                                        ₫</span>
                                </div>
                            </div>
                        </div>

                        <!-- Auto record debt checkbox -->
                        <label
                            class="flex items-start gap-3 p-3 bg-surface rounded-xl border border-outline-variant/60 cursor-pointer hover:bg-surface-container transition-colors select-none">
                            <input type="checkbox" x-model="allowDebt" :disabled="isClosing"
                                class="mt-0.5 rounded border-outline-variant text-primary focus:ring-primary h-4 w-4">
                            <div class="flex flex-col">
                                <span
                                    class="font-bold text-on-surface">{{ __('admin.auto_record_debts_label') }}</span>
                                <span
                                    class="text-[11px] text-outline mt-0.5 leading-relaxed">{{ __('admin.auto_record_debts_desc') }}</span>
                            </div>
                        </label>

                        <!-- Notice -->
                        <p class="text-[11px] text-outline leading-relaxed px-1">
                            {{ __('admin.close_campaign_notice_text') }}
                        </p>

                        <!-- Modal Actions -->
                        <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                            <button type="button" @click="closeConfirmModalOpen = false" :disabled="isClosing"
                                class="px-4 py-2.5 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50">
                                {{ __('admin.cancel') }}
                            </button>
                            <button type="button" @click="executeCloseCampaign()" :disabled="isClosing"
                                class="px-4 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold flex items-center gap-2 transition-colors cursor-pointer shadow-xs disabled:opacity-50">
                                <template x-if="isClosing">
                                    <span class="flex items-center gap-2">
                                        <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span x-text="'{{ __('admin.closing_in_progress') }}'"></span>
                                    </span>
                                </template>
                                <template x-if="!isClosing">
                                    <span class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px]">lock</span>
                                        <span>{{ __('admin.confirm_close_campaign_btn_text') }}</span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: ORDER DETAILS -->
            <div x-show="orderDetailModalOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                style="display: none;">
                <div @click.outside="orderDetailModalOpen = false"
                    class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                    <template x-if="selectedOrder">
                        <div class="flex flex-col h-full overflow-hidden">
                            <!-- Modal Header -->
                            <div
                                class="p-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low shrink-0">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">receipt</span>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2">
                                            <span>{{ __('admin.order_detail') }}</span>
                                            <span class="text-xs font-mono text-primary"
                                                x-text="selectedOrder.code"></span>
                                        </h3>
                                        <p class="text-[11px] text-outline"
                                            x-text="selectedOrder.created_at ? ('{{ __('admin.order_created_at') }}: ' + selectedOrder.created_at) : ''">
                                        </p>
                                    </div>
                                </div>
                                <button type="button" @click="orderDetailModalOpen = false"
                                    class="text-outline hover:text-on-surface cursor-pointer p-1 rounded-md hover:bg-surface-container">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>

                            <!-- Modal Body (Scrollable) -->
                            <div class="p-5 space-y-4 text-xs overflow-y-auto grow">
                                <!-- Member Info -->
                                <div
                                    class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span
                                            class="text-[11px] text-outline block">{{ __('admin.ordered_by') }}</span>
                                        <span class="font-bold text-on-surface text-sm block mt-0.5"
                                            x-text="selectedOrder.user_name"></span>
                                        <div class="flex items-center gap-2 mt-1 text-[11px] text-outline font-mono">
                                            <span x-show="selectedOrder.user_code"
                                                x-text="'#' + selectedOrder.user_code"></span>
                                            <span x-show="selectedOrder.email" x-text="selectedOrder.email"></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span x-show="selectedOrder.desk_location"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-xs font-medium border border-outline-variant/60">
                                            <span class="material-symbols-outlined text-[13px]">location_on</span>
                                            <span x-text="selectedOrder.desk_location"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Items List -->
                                <div class="space-y-2">
                                    <h4
                                        class="font-bold text-on-surface text-xs uppercase tracking-wider font-mono text-outline">
                                        {{ __('admin.order_items_list') }}
                                    </h4>
                                    <div
                                        class="border border-outline-variant rounded-xl overflow-hidden divide-y divide-outline-variant/40">
                                        <template x-for="(item, idx) in selectedOrder.items" :key="item.id || idx">
                                            <div
                                                class="p-3 bg-surface-container-lowest hover:bg-surface-container-low/50 transition-colors">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="space-y-1">
                                                        <div class="font-semibold text-on-surface">
                                                            <span x-text="item.name"></span>
                                                            <span x-show="item.size" class="text-outline font-normal"
                                                                x-text="'(' + item.size + ')'"></span>
                                                        </div>
                                                        <template x-if="item.toppings && item.toppings.length">
                                                            <div class="flex flex-wrap gap-1 pt-0.5">
                                                                <template x-for="top in item.toppings"
                                                                    :key="top.name">
                                                                    <span
                                                                        class="inline-block px-1.5 py-0.5 rounded bg-surface-container text-[10px] text-outline border border-outline-variant/50">
                                                                        + <span x-text="top.name"></span>
                                                                        <span x-show="top.price > 0"
                                                                            x-text="' (' + formatCurrency(top.price) + ')'"></span>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <div x-show="item.note"
                                                            class="text-[11px] text-amber-800 bg-amber-50/80 px-2 py-0.5 rounded border border-amber-200/60 mt-1 italic">
                                                            📝 <span x-text="item.note"></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right shrink-0">
                                                        <div class="font-bold text-on-surface font-mono"
                                                            x-text="formatCurrency(item.line_subtotal || item.total_amount)">
                                                        </div>
                                                        <div class="text-[11px] text-outline font-mono"
                                                            x-text="item.quantity + ' x ' + formatCurrency(item.unit_price)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Order Note if any -->
                                <div x-show="selectedOrder.note"
                                    class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 space-y-1">
                                    <span class="font-bold text-[11px] flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[15px]">description</span>
                                        <span>{{ __('admin.order_note') }}</span>
                                    </span>
                                    <p class="text-xs" x-text="selectedOrder.note"></p>
                                </div>

                                <!-- Financial Summary -->
                                <div
                                    class="bg-surface-container-low border border-outline-variant/60 rounded-xl p-3.5 space-y-2">
                                    <div class="flex justify-between items-center text-outline">
                                        <span>{{ __('admin.gross_bill') }}</span>
                                        <span class="font-mono text-on-surface font-semibold"
                                            x-text="formatCurrency(selectedOrder.subtotal)"></span>
                                    </div>
                                    <div x-show="selectedOrder.sponsor_amount > 0"
                                        class="flex justify-between items-center text-emerald-700">
                                        <span>{{ __('admin.subsidy') }}</span>
                                        <span class="font-mono font-semibold"
                                            x-text="'-' + formatCurrency(selectedOrder.sponsor_amount)"></span>
                                    </div>
                                    <div
                                        class="pt-2 border-t border-outline-variant/40 flex justify-between items-center text-sm font-bold text-on-surface">
                                        <span>{{ __('admin.payable') }}</span>
                                        <span class="font-mono text-primary text-base"
                                            x-text="formatCurrency(selectedOrder.final_amount)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div
                                class="p-3 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-end shrink-0">
                                <button type="button" @click="orderDetailModalOpen = false"
                                    class="px-4 py-2 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-semibold transition-colors cursor-pointer">
                                    {{ __('admin.close') }}
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- MODAL: DEBT DETAILS -->
            <div x-show="debtDetailModalOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                style="display: none;">
                <div @click.outside="debtDetailModalOpen = false"
                    class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                    <template x-if="selectedDebt">
                        <div class="flex flex-col h-full overflow-hidden">
                            <!-- Modal Header -->
                            <div
                                class="p-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low shrink-0">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                                        <span
                                            class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2">
                                            <span>{{ __('admin.debt_detail') }}</span>
                                            <span class="text-xs font-mono text-primary"
                                                x-text="'#' + selectedDebt.code"></span>
                                        </h3>
                                        <p class="text-[11px] text-outline"
                                            x-text="selectedDebt.created_at ? ('{{ __('admin.created_at') }}: ' + selectedDebt.created_at) : ''">
                                        </p>
                                    </div>
                                </div>
                                <button type="button" @click="debtDetailModalOpen = false"
                                    class="text-outline hover:text-on-surface cursor-pointer p-1 rounded-md hover:bg-surface-container">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-5 space-y-4 text-xs overflow-y-auto grow">
                                <!-- Member Info -->
                                <div
                                    class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span
                                            class="text-[11px] text-outline block">{{ __('admin.debt_member') }}</span>
                                        <span class="font-bold text-on-surface text-sm block mt-0.5"
                                            x-text="selectedDebt.user_name"></span>
                                        <div class="flex items-center gap-2 mt-1 text-[11px] text-outline font-mono">
                                            <span x-show="selectedDebt.user_code"
                                                x-text="'#' + selectedDebt.user_code"></span>
                                            <span x-show="selectedDebt.email" x-text="selectedDebt.email"></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span x-show="selectedDebt.desk_location"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-xs font-medium border border-outline-variant/60">
                                            <span class="material-symbols-outlined text-[13px]">location_on</span>
                                            <span x-text="selectedDebt.desk_location"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Transfer Content / Note -->
                                <div
                                    class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-1">
                                    <span
                                        class="text-[11px] text-outline block font-medium">{{ __('admin.request_content') }}</span>
                                    <div class="text-xs font-mono font-bold text-primary" x-text="selectedDebt.note">
                                    </div>
                                </div>

                                <!-- Financial Summary Grid -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-xl">
                                        <span
                                            class="text-[11px] text-outline block">{{ __('admin.original_debt') }}</span>
                                        <span class="text-sm font-bold font-mono text-on-surface mt-0.5 block"
                                            x-text="formatCurrency(selectedDebt.original_amount)"></span>
                                    </div>
                                    <div
                                        class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-xl">
                                        <span
                                            class="text-[11px] text-outline block">{{ __('admin.paid_debt') }}</span>
                                        <span class="text-sm font-bold font-mono text-emerald-700 mt-0.5 block"
                                            x-text="formatCurrency(selectedDebt.paid_amount)"></span>
                                    </div>
                                </div>

                                <!-- Remaining Balance Card -->
                                <div class="p-4 rounded-xl border flex items-center justify-between"
                                    :class="selectedDebt.remaining_amount > 0 ?
                                        'bg-amber-50/80 border-amber-200 text-amber-950' :
                                        'bg-emerald-50/80 border-emerald-200 text-emerald-950'">
                                    <div>
                                        <span class="text-[11px] font-medium uppercase tracking-wider block"
                                            :class="selectedDebt.remaining_amount > 0 ? 'text-amber-800' : 'text-emerald-800'">
                                            {{ __('admin.remaining_debt') }}
                                        </span>
                                        <span class="text-xl font-bold font-mono mt-0.5 block"
                                            :class="selectedDebt.remaining_amount > 0 ? 'text-amber-700' : 'text-emerald-700'"
                                            x-text="formatCurrency(selectedDebt.remaining_amount)">
                                        </span>
                                    </div>
                                    <span class="material-symbols-outlined text-[28px]"
                                        :class="selectedDebt.remaining_amount > 0 ? 'text-amber-500' : 'text-emerald-500'">
                                        account_balance
                                    </span>
                                </div>

                                <!-- Status Quick Change inside Modal -->
                                <div
                                    class="p-3 bg-surface-container-low border border-outline-variant/60 rounded-xl flex items-center justify-between gap-3">
                                    <span
                                        class="text-xs font-semibold text-on-surface">{{ __('admin.status') }}:</span>
                                    <select :value="debtsStatus[selectedDebt.id]"
                                        :disabled="isUpdatingDebt[selectedDebt.id]"
                                        @change="updateDebtStatus(selectedDebt.id, $event.target.value)"
                                        class="h-9 px-3 text-xs font-semibold rounded-lg border bg-surface transition-colors cursor-pointer">
                                        <option value="unpaid">{{ __('admin.filter_debt_unpaid') }}</option>
                                        <option value="paid">{{ __('admin.filter_debt_paid') }}</option>
                                        <option value="waived">{{ __('admin.status_waived') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div
                                class="p-3 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-end shrink-0">
                                <button type="button" @click="debtDetailModalOpen = false"
                                    class="px-4 py-2 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-semibold transition-colors cursor-pointer">
                                    {{ __('admin.close') }}
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- MODAL: CONFIRM ITEMS HAVE ARRIVED -->
            <div x-show="confirmDeliveryModalOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                style="display: none;">
                <div @click.outside="if (!isDeliveringLoading) confirmDeliveryModalOpen = false"
                    class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <div
                        class="p-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">delivery_dining</span>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface">{{ __('admin.confirm_delivery_title') }}</h3>
                                <p class="text-[11px] text-outline font-mono">#{{ $campaign->code }} · {{ $campaign->name }}</p>
                            </div>
                        </div>
                        <button type="button" @click="confirmDeliveryModalOpen = false" :disabled="isDeliveringLoading"
                            class="text-outline hover:text-on-surface disabled:opacity-40 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <div class="p-3.5 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-2">
                            <p class="text-xs text-on-surface leading-relaxed">
                                {{ __('admin.confirm_mark_delivering_prompt') }}
                            </p>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-2 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                            <button type="button" @click="confirmDeliveryModalOpen = false" :disabled="isDeliveringLoading"
                                class="px-4 py-2.5 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50">
                                {{ __('admin.cancel') }}
                            </button>
                            <button type="button" @click="executeMarkDelivering()" :disabled="isDeliveringLoading"
                                class="px-4 py-2.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold flex items-center gap-2 transition-colors cursor-pointer shadow-xs disabled:opacity-50">
                                <template x-if="isDeliveringLoading">
                                    <span class="flex items-center gap-2">
                                        <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>{{ __('admin.processing') }}...</span>
                                    </span>
                                </template>
                                <template x-if="!isDeliveringLoading">
                                    <span class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        <span>{{ __('admin.confirm_delivery_btn') }}</span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-admin.layout>
