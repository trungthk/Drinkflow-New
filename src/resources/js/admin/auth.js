/**
 * Admin Authentication & Security Helpers
 */
export function initAdminAuth() {
    const passwordInput = document.querySelector('#admin-password');
    const passwordToggle = document.querySelector('#toggle-admin-password');
    if (passwordInput && passwordToggle) {
        passwordToggle.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            const icon = passwordToggle.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = isPassword ? 'visibility_off' : 'visibility';
            }
        });
    }
}
