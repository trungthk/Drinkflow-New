/**
 * Superadmin Rooms Management Controller
 */
export function initSuperadminRooms() {
    window.deleteRoom = async function (id) {
        if (confirm('Delete room?')) {
            try {
                if (typeof window.dfApi === 'function') {
                    await window.dfApi(`/superadmin/rooms/${id}`, {
                        method: 'DELETE',
                    });
                } else {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                    const res = await fetch(`/superadmin/rooms/${id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                }
                window.location.reload();
            } catch (err) {
                console.error(err);
                alert(err.message || 'Error deleting room');
            }
        }
    };

    window.createRoom = async function (endpoint) {
        const name = prompt('Room name');
        if (!name) return;
        const slug = prompt('Slug');
        if (!slug) return;

        const targetUrl = endpoint || '/superadmin/rooms';
        try {
            if (typeof window.dfApi === 'function') {
                await window.dfApi(targetUrl, {
                    method: 'POST',
                    body: { name, slug },
                });
            } else {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const res = await fetch(targetUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ name, slug }),
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
            }
            window.location.reload();
        } catch (err) {
            console.error(err);
            alert(err.message || 'Error creating room');
        }
    };

    // Declarative event delegation
    document.addEventListener('click', (event) => {
        const createBtn = event.target.closest('[data-action="create-room"]');
        if (createBtn) {
            window.createRoom(createBtn.dataset.endpoint);
            return;
        }

        const deleteBtn = event.target.closest('[data-action="delete-room"]');
        if (deleteBtn) {
            window.deleteRoom(deleteBtn.dataset.roomId);
        }
    });
}
