{{--
    VietQR Payment Card Component
    ==============================
    @component App\View\Components\VietQrPayment

    Props (all optional except qr-src):
    • qr-src             – QR image URL or data URI  (required)
    • amount             – Amount in VNĐ             (default: 0)
    • bank-name          – Bank name label            (default: '')
    • account-name       – Account holder name        (default: '')
    • account-number     – Account number             (default: '')
    • description        – Transfer reference / memo  (default: '')
    • size               – QR card width in px        (default: 300)
    • animation-duration – Snake animation seconds    (default: 2.5)
    • snake-color        – Animated border color      (default: #16a34a)
    • track-color        – Static border track color  (default: #e2e8f0)
    • border-radius      – Card corner radius in px   (default: 20)
--}}

@php
    /*
     * The SVG snake uses a fixed 100-unit coordinate system (pathLength="100").
     * We only need one scoped <style> block per component instance, keyed by
     * $componentId so multiple instances on the same page don't collide.
     */
    $id       = $componentId;              // e.g. "vqr-a1b2c3d4"
    $dur      = $durationCss();           // e.g. "2.5s"
    $fmtAmt   = $formattedAmount();       // e.g. "150.000đ"

    // SVG corner rx/ry – must match the card's border-radius visually.
    // Since the SVG uses a 100-unit space we proportionally scale the radius.
    // At size=300px, border-radius=20px → rx ≈ 6.67 units. We'll keep it as
    // a percentage-like value relative to the card size.
    $rxRaw    = max(1, round(($borderRadius / $size) * 100, 2));
    $rx       = min($rxRaw, 15); // cap so it never looks like a circle
@endphp

{{-- ── Scoped <style> block (rendered once per component instance) ──────── --}}
<style>
  /* ── CSS Custom Properties ───────────────────────────────────────────── */
  #{{ $id }} {
    --vietqr-snake-color:       {{ $snakeColor }};
    --vietqr-track-color:       {{ $trackColor }};
    --vietqr-animation-duration: {{ $dur }};
    --vietqr-border-radius:     {{ $borderRadius }}px;
    --vietqr-size:              {{ $size }}px;
  }

  /* ── Card shell ──────────────────────────────────────────────────────── */
  #{{ $id }}.vietqr-card {
    position:        relative;
    display:         inline-flex;
    flex-direction:  column;
    align-items:     center;
    gap:             0;
    width:           var(--vietqr-size);
    max-width:       100%;
    background:      #ffffff;
    border-radius:   var(--vietqr-border-radius);
    box-shadow:      0 4px 24px rgba(0, 0, 0, 0.10), 0 1px 4px rgba(0, 0, 0, 0.06);
    padding:         0;
    overflow:        hidden;
    font-family:     'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    box-sizing:      border-box;
  }

  /* ── SVG overlay (snake + track) – absolutely positioned, pointer-events: none ── */
  #{{ $id }} .vietqr-svg-overlay {
    position:        absolute;
    inset:           0;
    width:           100%;
    height:          100%;
    pointer-events:  none;
    z-index:         10;
    overflow:        visible;
  }

  /* ── Static border track (always visible, thin, muted) ──────────────── */
  #{{ $id }} .vietqr-track {
    fill:            none;
    stroke:          var(--vietqr-track-color);
    stroke-width:    2.5;
    stroke-linecap:  round;
    stroke-linejoin: round;
  }

  /* ── Animated snake border ───────────────────────────────────────────── */
  #{{ $id }} .vietqr-snake {
    fill:            none;
    stroke:          var(--vietqr-snake-color);
    stroke-width:    2.8;
    stroke-linecap:  round;
    stroke-linejoin: round;
    /* start: tiny snake at top-left, not yet grown */
    stroke-dasharray:  5 95;
    stroke-dashoffset: 0;
    animation:
      vietqr-snake-grow-{{ $id }}   var(--vietqr-animation-duration) ease-in-out infinite,
      vietqr-snake-travel-{{ $id }}  var(--vietqr-animation-duration) linear infinite;
  }

  /* ── Snake grow keyframes ────────────────────────────────────────────── */
  @keyframes vietqr-snake-grow-{{ $id }} {
    0%   { stroke-dasharray:   5  95; }
    15%  { stroke-dasharray:  15  85; }
    30%  { stroke-dasharray:  30  70; }
    50%  { stroke-dasharray:  50  50; }
    70%  { stroke-dasharray:  75  25; }
    88%, 100% { stroke-dasharray: 100   0; }
  }

  /* ── Snake travel (offset) keyframes — makes it march around the rect ── */
  @keyframes vietqr-snake-travel-{{ $id }} {
    0%   { stroke-dashoffset:   0; }
    100% { stroke-dashoffset: -100; }
  }

  /* ── Bank name header ────────────────────────────────────────────────── */
  #{{ $id }} .vietqr-header {
    width:           100%;
    padding:         14px 20px 10px;
    box-sizing:      border-box;
    text-align:      center;
    border-bottom:   1px solid #f1f5f9;
  }

  #{{ $id }} .vietqr-bank-name {
    font-size:       13px;
    font-weight:     600;
    letter-spacing:  0.04em;
    text-transform:  uppercase;
    color:           #475569;
    margin:          0;
  }

  /* ── QR image wrapper ────────────────────────────────────────────────── */
  #{{ $id }} .vietqr-qr-wrapper {
    padding:         18px 24px 14px;
    display:         flex;
    align-items:     center;
    justify-content: center;
    width:           100%;
    box-sizing:      border-box;
  }

  #{{ $id }} .vietqr-qr-img {
    display:         block;
    width:           100%;
    height:          auto;
    border-radius:   8px;
    /* Never rotate, never overlay — pure static image */
    image-rendering: pixelated; /* keeps QR modules crisp on hi-dpi */
  }

  /* ── Amount display ──────────────────────────────────────────────────── */
  #{{ $id }} .vietqr-amount {
    font-size:       22px;
    font-weight:     700;
    color:           var(--vietqr-snake-color);
    letter-spacing:  -0.02em;
    padding:         2px 20px 4px;
    text-align:      center;
    width:           100%;
    box-sizing:      border-box;
  }

  /* ── Account info footer ─────────────────────────────────────────────── */
  #{{ $id }} .vietqr-footer {
    width:           100%;
    padding:         10px 20px 16px;
    box-sizing:      border-box;
    border-top:      1px solid #f1f5f9;
    text-align:      center;
  }

  #{{ $id }} .vietqr-account-name {
    font-size:       13px;
    font-weight:     600;
    color:           #1e293b;
    margin:          0 0 2px;
    letter-spacing:  0.01em;
  }

  #{{ $id }} .vietqr-account-number {
    font-size:       13px;
    font-weight:     400;
    color:           #64748b;
    letter-spacing:  0.06em;
    font-variant-numeric: tabular-nums;
    margin:          0 0 6px;
  }

  #{{ $id }} .vietqr-desc-label {
    font-size:       11px;
    font-weight:     500;
    color:           #94a3b8;
    text-transform:  uppercase;
    letter-spacing:  0.08em;
    margin:          0 0 2px;
  }

  #{{ $id }} .vietqr-desc-value {
    font-size:       12px;
    font-weight:     600;
    color:           #475569;
    font-family:     'Courier New', Courier, monospace;
    background:      #f8fafc;
    border:          1px solid #e2e8f0;
    border-radius:   6px;
    padding:         3px 10px;
    display:         inline-block;
    letter-spacing:  0.05em;
  }

  /* ── Reduced-motion override ─────────────────────────────────────────── */
  @media (prefers-reduced-motion: reduce) {
    #{{ $id }} .vietqr-snake {
      animation:         none;
      stroke-dasharray:  100 0;
      stroke-dashoffset: 0;
    }
  }
</style>

{{-- ── Component markup ────────────────────────────────────────────────────── --}}
<div
    id="{{ $id }}"
    class="vietqr-card"
    role="region"
    aria-label="{{ __('Thông tin thanh toán VietQR') }}"
    style="width: {{ $size }}px; max-width: 100%;"
>

  {{-- ── SVG snake-border overlay (sits above everything, pointer-events:none) --}}
  {{--
      The SVG viewport is 100×100 with preserveAspectRatio="none" so it
      stretches to match the card's actual pixel dimensions automatically.
      pathLength="100" on both <rect> elements means all dasharray values
      are expressed as percentages of the perimeter — easy to reason about.
  --}}
  <svg
      class="vietqr-svg-overlay"
      viewBox="0 0 100 100"
      preserveAspectRatio="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
      focusable="false"
  >
    {{-- Static border track (always visible) --}}
    <rect
        class="vietqr-track"
        x="1.25"
        y="1.25"
        width="97.5"
        height="97.5"
        rx="{{ $rx }}"
        ry="{{ $rx }}"
        pathLength="100"
    />
    {{-- Animated snake border --}}
    <rect
        class="vietqr-snake"
        x="1.25"
        y="1.25"
        width="97.5"
        height="97.5"
        rx="{{ $rx }}"
        ry="{{ $rx }}"
        pathLength="100"
    />
  </svg>

  {{-- ── Bank name header ──────────────────────────────────────────────────── --}}
  @if($bankName !== '')
  <div class="vietqr-header">
    <p class="vietqr-bank-name">{{ $bankName }}</p>
  </div>
  @endif

  {{-- ── QR image ──────────────────────────────────────────────────────────── --}}
  <div class="vietqr-qr-wrapper">
    <img
        src="{{ $qrSrc }}"
        alt="{{ __('Mã VietQR thanh toán') }}"
        class="vietqr-qr-img"
        loading="lazy"
        decoding="async"
        onerror="this.style.opacity='0.3';this.title='{{ __('Không thể tải mã QR') }}';"
    >
  </div>

  {{-- ── Amount ─────────────────────────────────────────────────────────────── --}}
  @if($amount > 0)
  <div class="vietqr-amount" aria-label="{{ __('Số tiền cần thanh toán') }}: {{ $fmtAmt }}">
    {{ $fmtAmt }}
  </div>
  @endif

  {{-- ── Account info & reference ───────────────────────────────────────────── --}}
  @if($accountName !== '' || $accountNumber !== '' || $description !== '')
  <div class="vietqr-footer">
    @if($accountName !== '')
    <p class="vietqr-account-name">{{ $accountName }}</p>
    @endif
    @if($accountNumber !== '')
    <p class="vietqr-account-number">{{ $accountNumber }}</p>
    @endif
    @if($description !== '')
    <p class="vietqr-desc-label">{{ __('Nội dung chuyển khoản') }}</p>
    <span class="vietqr-desc-value">{{ $description }}</span>
    @endif
  </div>
  @endif

</div>
