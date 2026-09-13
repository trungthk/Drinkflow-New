<!-- Public Loading Page & Submit Overlay Component -->
<div id="public-page-loading"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-white/75 backdrop-blur-xs transition-opacity duration-200 opacity-100"
     role="status"
     aria-live="polite"
     aria-label="{{ __('public.loading.processing') }}">
  <div class="flex flex-col items-center gap-2.5 select-none transform scale-100 transition-all duration-200" id="public-loading-card">
    <!-- Gentle Minimal Brand Spinner -->
    <div class="relative w-10 h-10 flex items-center justify-center">
      <div class="w-10 h-10 rounded-full border-2 border-emerald-100 border-t-[#006948] animate-spin"></div>
      <span class="material-symbols-outlined text-[18px] text-[#006948] absolute animate-pulse">local_cafe</span>
    </div>

    <!-- Minimal, Soft Single Line Text -->
    <p class="text-xs font-medium text-slate-500 tracking-wide" id="public-loading-title">
      {{ __('public.loading.processing') }}
    </p>
  </div>
</div>

<script>
  (function() {
    const overlay = document.getElementById('public-page-loading');
    const card = document.getElementById('public-loading-card');
    const title = document.getElementById('public-loading-title');
    let autoHideTimer = null;

    const defaultTitle = @json(__('public.loading.processing'));
    const connectingGoogleTitle = @json(__('public.loading.connecting_google'));
    const submittingTitle = @json(__('public.loading.submitting'));

    window.showPublicLoading = function(t) {
      if (!overlay || !card) return;

      if (title) title.textContent = t || defaultTitle;

      overlay.classList.remove('hidden', 'pointer-events-none');
      overlay.classList.add('flex');

      requestAnimationFrame(() => {
        overlay.classList.remove('opacity-0');
        overlay.classList.add('opacity-100');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
      });

      clearTimeout(autoHideTimer);
      autoHideTimer = setTimeout(() => {
        window.hidePublicLoading();
      }, 10000);
    };

    window.hidePublicLoading = function() {
      if (!overlay || !card) return;

      overlay.classList.remove('opacity-100');
      overlay.classList.add('opacity-0');
      card.classList.remove('scale-100');
      card.classList.add('scale-95');

      setTimeout(() => {
        overlay.classList.add('hidden', 'pointer-events-none');
        overlay.classList.remove('flex');
      }, 200);

      clearTimeout(autoHideTimer);
    };

    // Smooth subtle reveal on page readiness
    function onInitialLoad() {
      setTimeout(() => {
        window.hidePublicLoading();
      }, 180);
    }

    if (document.readyState === 'complete') {
      onInitialLoad();
    } else {
      window.addEventListener('load', onInitialLoad);
    }

    // Auto-attach to all forms on submit
    document.addEventListener('submit', function(e) {
      const form = e.target;
      if (!form || form.hasAttribute('data-no-loading')) return;

      if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
      let customTitle = submittingTitle;
      if (submitBtn) {
        if (submitBtn.hasAttribute('data-loading-title')) {
          customTitle = submitBtn.getAttribute('data-loading-title');
        } else if (submitBtn.textContent.trim()) {
          const raw = submitBtn.textContent.trim().replace(/\s+/g, ' ');
          if (raw.length <= 20) {
            customTitle = `Đang ${raw.toLowerCase()}...`;
          }
        }
      }

      window.showPublicLoading(customTitle);

      if (submitBtn && !submitBtn.disabled) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-wait');
      }
    });

    // Handle Google SSO clicks
    document.addEventListener('click', function(e) {
      const ssoLink = e.target.closest('a[href*="auth/google"], .google-sso-link, .btn-google-sso-direct');
      if (ssoLink) {
        window.showPublicLoading(connectingGoogleTitle);
      }
    });

    // Reset loading on bfcache
    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        window.hidePublicLoading();
        document.querySelectorAll('button[disabled], a.pointer-events-none').forEach(btn => {
          btn.disabled = false;
          btn.classList.remove('opacity-75', 'cursor-wait', 'pointer-events-none');
          if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
          }
        });
      }
    });
  })();
</script>
