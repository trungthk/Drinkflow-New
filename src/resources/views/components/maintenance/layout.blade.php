{{-- Standalone layout for the maintenance page: no header, footer, navigation or Vite assets.
     Styles are inline so the page still renders while a deploy is rebuilding public/build. --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? __('errors.maintenance.page_title') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #f8f9ff;
            color: #0b1c30;
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Hiragino Sans", "Noto Sans JP", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .mt-card {
            width: 100%;
            max-width: 560px;
            padding: 40px 32px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 30px -12px rgba(11, 28, 48, .15);
            text-align: center;
        }
        .mt-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            border-radius: 16px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #d97706;
        }
        .mt-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            padding: 2px 10px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .04em;
        }
        .mt-dot { width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; animation: mt-pulse 1.6s ease-in-out infinite; }
        @keyframes mt-pulse { 50% { opacity: .35; } }
        .mt-title { margin: 0 0 12px; font-size: 26px; line-height: 1.25; font-weight: 700; letter-spacing: -.01em; }
        .mt-desc { margin: 0 auto; max-width: 440px; color: #45464d; font-size: 15px; line-height: 1.6; }
        .mt-window {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 28px 0 12px;
            text-align: left;
        }
        .mt-window div { padding: 14px 16px; border-radius: 12px; background: #f8f9ff; border: 1px solid #e2e8f0; }
        .mt-window dt { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #64748b; }
        .mt-window dd { margin: 4px 0 0; font-size: 16px; font-weight: 600; }
        .mt-note { margin: 0 0 28px; color: #45464d; font-size: 13px; line-height: 1.5; }
        .mt-refresh {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 0;
            border-radius: 10px;
            background: #006948;
            color: #fff;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .15s;
        }
        .mt-refresh:hover { background: #00855c; }
        .mt-refresh:focus-visible { outline: 2px solid #006948; outline-offset: 3px; }
        .mt-retry { margin: 12px 0 0; color: #64748b; font-size: 12px; }
        .mt-retry strong { color: #0b1c30; }
        @media (max-width: 480px) {
            .mt-card { padding: 32px 20px; }
            .mt-title { font-size: 22px; }
            .mt-window { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) { .mt-dot { animation: none; } }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
