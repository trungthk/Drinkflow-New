/**
 * Admin VietQR Payment Accounts Controller
 */
export function initAdminPayments() {
    const form = document.querySelector('#add-payment-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!form) return;
    const qrModal = document.querySelector('#payment-qr-modal');
    const deleteModal = document.querySelector('#payment-delete-modal');
    let deleteId = null;

    window.previewQR = function(bankCode, accNumber, accName) {
        const qrCard = qrModal;
        const qrImg = document.querySelector('#qr-image');
        const qrDetails = document.querySelector('#qr-details');
        if (!qrCard || !qrImg || !qrDetails) return;

        const qrUrl = `https://img.vietqr.io/image/${bankCode}-${accNumber}-compact2.png?accountName=${encodeURIComponent(accName)}&amount=50000&addInfo=DRINKFLOW`;
        qrImg.src = qrUrl;
        qrDetails.textContent = `${bankCode} · ${accNumber} · ${accName}`;
        qrCard.classList.remove('hidden');
        qrCard.classList.add('flex');
    };

    window.editPaymentAccount = function(id, bankCode, bankName, number, name, isDefault) {
        document.querySelector('#payment-account-id').value = id;
        document.querySelector('#acc-bank-code').value = bankCode;
        document.querySelector('#acc-number').value = number;
        document.querySelector('#acc-name').value = name;
        document.querySelector('#acc-default').checked = isDefault;
        document.querySelector('#payment-form-title').textContent = 'Edit payment account';
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            const id = document.querySelector('#payment-account-id').value;
            const res = await fetch(`/admin/${roomSlug}/payment-accounts${id ? `/${id}` : ''}`, {
                method: id ? 'PATCH' : 'POST',
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
        deleteId = id;
        deleteModal?.classList.remove('hidden');
        deleteModal?.classList.add('flex');
    };
    document.querySelector('[data-close-qr]')?.addEventListener('click', () => { qrModal?.classList.add('hidden'); qrModal?.classList.remove('flex'); });
    document.querySelector('[data-close-delete]')?.addEventListener('click', () => { deleteModal?.classList.add('hidden'); deleteModal?.classList.remove('flex'); });
    document.querySelector('#confirm-payment-delete')?.addEventListener('click', async () => {
        if (!deleteId) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/payment-accounts/${deleteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) window.location.reload();
            else alert('Could not delete account.');
        } catch (e) {
            console.error(e);
        }
    });
}
