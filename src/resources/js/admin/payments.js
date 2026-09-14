/**
 * Admin VietQR Payment Accounts Controller
 */
export function initAdminPayments() {
    const form = document.querySelector('#add-payment-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!form && !document.querySelector('#qr-preview-card')) return;

    window.previewQR = function(bankCode, accNumber, accName) {
        const qrCard = document.querySelector('#qr-preview-card');
        const qrImg = document.querySelector('#qr-image');
        const qrDetails = document.querySelector('#qr-details');
        if (!qrCard || !qrImg || !qrDetails) return;

        const qrUrl = `https://img.vietqr.io/image/${bankCode}-${accNumber}-compact2.png?accountName=${encodeURIComponent(accName)}&amount=50000&addInfo=DRINKFLOW`;
        qrImg.src = qrUrl;
        qrDetails.textContent = `${bankCode} · ${accNumber} · ${accName}`;
        qrCard.classList.remove('hidden');
        qrCard.scrollIntoView({ behavior: 'smooth' });
    };

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const bankSelect = document.querySelector('#acc-bank-code');
        const bankCode = bankSelect.value;
        const bankName = bankSelect.options[bankSelect.selectedIndex]?.dataset.bankName || bankSelect.options[bankSelect.selectedIndex]?.text || bankCode;
        const accountNumber = document.querySelector('#acc-number').value.trim();
        const accountName = document.querySelector('#acc-name').value.trim();
        const branch = document.querySelector('#acc-branch').value.trim();
        const isDefault = document.querySelector('#acc-default').checked;

        try {
            const res = await fetch(`/admin/${roomSlug}/payment-accounts`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    bank_code: bankCode,
                    bank_name: bankName,
                    account_number: accountNumber,
                    account_name: accountName,
                    branch: branch,
                    is_default: isDefault,
                    status: 'active'
                })
            });
            if (res.ok) {
                window.location.reload();
            } else {
                alert('Could not save account.');
            }
        } catch (e) {
            console.error(e);
            alert('Server error.');
        }
    });

    window.deleteAccount = async function(id) {
        if (!confirm('Delete this account?')) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/payment-accounts/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) window.location.reload();
            else alert('Could not delete account.');
        } catch (e) {
            console.error(e);
        }
    };
}
