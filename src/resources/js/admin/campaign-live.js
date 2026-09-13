/**
 * Admin Campaign Live Monitor Component (Alpine Component)
 */
export function liveCampaignComponent(config = {}) {
    const roomSlug = config.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const campaignId = config.campaignId || window.__DF_CAMPAIGN_ID__ || null;

    const totalUsers = config.totalUsers ?? window.__DF_TOTAL_USERS__ ?? 1;
    const ordered = config.orderedUsers ?? window.__DF_ORDERED_USERS__ ?? 0;
    const declined = config.declinedUsers ?? window.__DF_DECLINED_USERS__ ?? 0;
    const pending = config.pendingUsers ?? window.__DF_PENDING_USERS__ ?? 0;

    return {
        countdownText: '00:00:00',
        deadline: config.deadline || window.__DF_CAMPAIGN_DEADLINE__ || '',
        roomTotalUsers: totalUsers,
        orderedCount: ordered,
        declinedCount: declined,
        pendingCount: pending,
        orderedPct: totalUsers > 0 ? Math.round((ordered / totalUsers) * 1000) / 10 : 0,
        declinedPct: totalUsers > 0 ? Math.round((declined / totalUsers) * 1000) / 10 : 0,
        pendingPct: totalUsers > 0 ? Math.round((pending / totalUsers) * 1000) / 10 : 0,
        summaryText: config.summaryText || window.__DF_CAMPAIGN_SUMMARY_TEXT__ || '',
        searchQuery: '',
        allowDebt: true,
        openCloseModal: false,

        init() {
            this.startCountdown();
        },

        startCountdown() {
            if (!this.deadline) {
                this.countdownText = '--:--:--';
                return;
            }
            const target = new Date(this.deadline).getTime();
            const timer = setInterval(() => {
                const now = Date.now();
                const diff = target - now;
                if (diff <= 0) {
                    this.countdownText = '00:00:00';
                    clearInterval(timer);
                    return;
                }
                const hrs = Math.floor(diff / (1000 * 60 * 60)).toString().padStart(2, '0');
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
                const secs = Math.floor((diff % (1000 * 60)) / 1000).toString().padStart(2, '0');
                this.countdownText = `${hrs}:${mins}:${secs}`;
            }, 1000);
        },

        matchesSearch(text) {
            if (!this.searchQuery) return true;
            return (text || '').toLowerCase().includes(this.searchQuery.toLowerCase());
        },

        async extendDeadline(minutes) {
            try {
                const res = await fetch(`/admin/${roomSlug}/campaigns/${campaignId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        deadline: new Date(Date.now() + minutes * 60000).toISOString()
                    })
                });
                if (res.ok) {
                    window.location.reload();
                }
            } catch (e) {
                alert('Error extending deadline: ' + e.message);
            }
        },

        copyOrderSummary() {
            const summary = this.summaryText || window.__DF_CAMPAIGN_SUMMARY_TEXT__ || '';
            if (summary) {
                navigator.clipboard.writeText(summary).then(() => {
                    alert('Đã sao chép tổng hợp đơn món!');
                });
            }
        },

        async cancelOrder(orderId) {
            if (!confirm(`Cancel order #ORD-${orderId}?`)) return;
            try {
                const res = await fetch(`/admin/${roomSlug}/orders/${orderId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    window.location.reload();
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        },

        async cancelCampaign() {
            if (!confirm('Cancel this entire campaign?')) return;
            try {
                const res = await fetch(`/admin/${roomSlug}/campaigns/${campaignId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    window.location.href = `/admin/${roomSlug}/campaigns`;
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        },

        async confirmCloseCampaign() {
            try {
                const res = await fetch(`/admin/${roomSlug}/campaigns/${campaignId}/close`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ allow_debt: this.allowDebt })
                });
                if (res.ok) {
                    window.location.href = `/admin/${roomSlug}/campaigns/${campaignId}?view=detail`;
                } else {
                    const err = await res.json();
                    alert(err.message || 'Error closing campaign');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        },

        openPriceModal(order) {
            const actualPrice = prompt(`Order #ORD-${order.id} actual price:`, order.total_amount);
            if (actualPrice !== null && !isNaN(actualPrice)) {
                fetch(`/admin/${roomSlug}/orders/${order.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        actual_price: parseInt(actualPrice, 10),
                        reason: 'Live control adjustment'
                    })
                }).then(r => r.json()).then(() => {
                    window.location.reload();
                }).catch(e => alert('Error: ' + e.message));
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.liveCampaignComponent = liveCampaignComponent;
}
