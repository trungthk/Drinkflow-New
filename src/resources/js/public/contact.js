/**
 * Public Contact Page Interactive Module (Captcha refresh, FAQ accordion, Success modal)
 */
export function initContactPage() {
    const refreshBtn = document.getElementById('refresh-captcha-btn');
    const captchaWrapper = document.getElementById('captcha-img-wrapper');

    async function refreshCaptcha(e) {
        if (e) e.preventDefault();
        const wrapper = document.getElementById('captcha-img-wrapper');
        const img = wrapper ? wrapper.querySelector('img') : null;
        const btn = document.getElementById('refresh-captcha-btn');
        const icon = btn ? btn.querySelector('.material-symbols-outlined') : null;

        if (icon) {
            icon.classList.add('rotate-180');
        }

        const captchaApiUrl = (wrapper && wrapper.dataset.captchaApi) || '/captcha/api/contact';
        const captchaFallbackUrl = (wrapper && wrapper.dataset.captchaFallback) || '/captcha/contact';

        try {
            const res = await fetch(captchaApiUrl + '?' + Date.now(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (res.ok) {
                const data = await res.json();
                if (data && data.img && img) {
                    img.src = data.img;
                } else if (img) {
                    img.src = captchaFallbackUrl + '?' + Date.now();
                }
            } else if (img) {
                img.src = captchaFallbackUrl + '?' + Date.now();
            }
        } catch (err) {
            if (img) {
                img.src = captchaFallbackUrl + '?' + Date.now();
            }
        } finally {
            setTimeout(() => {
                if (icon) icon.classList.remove('rotate-180');
            }, 300);
            const captchaInput = document.getElementById('captcha');
            if (captchaInput) {
                captchaInput.value = '';
            }
        }
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshCaptcha);
    }
    if (captchaWrapper) {
        captchaWrapper.addEventListener('click', refreshCaptcha);
    }

    // FAQ Accordion
    const toggles = document.querySelectorAll('.faq-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const content = toggle.nextElementSibling;
            const icon = toggle.querySelector('.material-symbols-outlined');
            if (!content) return;
            const isHidden = content.classList.contains('hidden');

            // Close all other FAQ contents
            document.querySelectorAll('.faq-content').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.faq-toggle .material-symbols-outlined').forEach(i => {
                i.textContent = 'expand_more';
                i.classList.remove('rotate-180');
            });

            if (isHidden) {
                content.classList.remove('hidden');
                if (icon) {
                    icon.textContent = 'expand_less';
                }
            }
        });
    });

    // Success Modal Close
    const closeBtn = document.getElementById('closeSuccessModalBtn');
    const modal = document.getElementById('successModal');
    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }
}
