/**
 * Global User Header Interactive Module
 * Handles Language dropdown, Profile menu, and Notification dropdown
 */
export function initGlobalHeader() {
    // Language dropdown toggle
    const langBtn = document.getElementById('global-lang-btn');
    const langMenu = document.getElementById('global-lang-menu');
    const langSelector = document.getElementById('global-lang-selector');

    if (langBtn && langMenu) {
        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = !langMenu.classList.contains('hidden');
            if (isOpen) {
                langMenu.classList.add('hidden');
                langBtn.setAttribute('aria-expanded', 'false');
            } else {
                langMenu.classList.remove('hidden');
                langBtn.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(e) {
            if (langSelector && !langSelector.contains(e.target)) {
                langMenu.classList.add('hidden');
                langBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // User profile dropdown toggle
    const userBtn = document.getElementById('global-user-menu-btn');
    const userDropdown = document.getElementById('global-user-dropdown');
    const userChevron = document.getElementById('global-user-chevron');
    const userWrapper = document.getElementById('global-user-menu-wrapper');

    if (userBtn && userDropdown) {
        function toggleUserDropdown(show) {
            const isHidden = userDropdown.classList.contains('hidden');
            const shouldShow = typeof show === 'boolean' ? show : isHidden;
            if (shouldShow) {
                userDropdown.classList.remove('hidden');
                userBtn.setAttribute('aria-expanded', 'true');
                if (userChevron) userChevron.style.transform = 'rotate(180deg)';
            } else {
                userDropdown.classList.add('hidden');
                userBtn.setAttribute('aria-expanded', 'false');
                if (userChevron) userChevron.style.transform = 'rotate(0deg)';
            }
        }

        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleUserDropdown();
        });

        document.addEventListener('click', function(e) {
            if (userWrapper && !userWrapper.contains(e.target)) {
                toggleUserDropdown(false);
            }
        });
    }

    // Notifications dropdown toggle
    const notifBtn = document.getElementById('global-notification-btn');
    const notifDropdown = document.getElementById('global-notification-dropdown');

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function(e) {
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
        });
    }

    const markAllReadBtn = document.getElementById('global-mark-all-read-btn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async function() {
            const endpoint = markAllReadBtn.dataset.readAllUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!endpoint || !csrfToken || markAllReadBtn.disabled) return;

            markAllReadBtn.disabled = true;
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error('Unable to mark notifications as read.');

                const badge = document.getElementById('global-notif-badge');
                if (badge) badge.textContent = '0';
                const allReadText = markAllReadBtn.dataset.readText || 'Đã đọc tất cả';
                this.textContent = allReadText;
                this.classList.add('opacity-50', 'pointer-events-none');
            } catch (error) {
                console.error(error);
                markAllReadBtn.disabled = false;
            }
        });
    }
}
