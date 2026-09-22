@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    // Checks common.php (active/blocked/…) then health.php (ok/error/running/…) — matching the
    // client-side statusPill() helper in resources/js/superadmin/shared.js used by AJAX-rendered
    // tables — and falls back to the raw value itself when neither has a translation for it.
    $label = __('superadmin.common.'.$value);
    if ($label === 'superadmin.common.'.$value) {
        $healthLabel = __('superadmin.health.'.$value);
        $label = $healthLabel === 'superadmin.health.'.$value ? $value : $healthLabel;
    }
@endphp
<span class="status-pill status-{{ $value }}"><span class="status-dot"></span>{{ $label }}</span>
