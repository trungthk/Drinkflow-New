@props([
    'title' => null,
    'description' => null,
    'ogTitle' => null,
    'ogDescription' => null,
    'ogType' => 'website',
    'activeTab' => 'about',
    'version' => config('app.version', 'v2.3.0'),
    'termsUrl' => url('/terms'),
    'versionsUrl' => url('/versions'),
    'contactUrl' => route('contact'),
    'googleAuthUrl' => route('auth.google'),
])

<!DOCTYPE html>
<html class="light h-full" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    
    <!-- SEO / Metadata -->
    <title>{{ $title ?? __('public.meta.title') }}</title>
    <meta name="description" content="{{ $description ?? __('public.meta.description') }}"/>
    <meta property="og:title" content="{{ $ogTitle ?? ($title ?? __('public.meta.og_title')) }}"/>
    <meta property="og:description" content="{{ $ogDescription ?? ($description ?? __('public.meta.og_description')) }}"/>
    <meta property="og:type" content="{{ $ogType }}"/>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin=""/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>

    <!-- Tailwind CSS Engine & Custom Config -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#006948",
                        "primary-container": "#00855d",
                        "primary-fixed": "#85f8c4",
                        "primary-fixed-dim": "#68dba9",
                        "on-primary": "#ffffff",
                        "secondary": "#006c49",
                        "secondary-container": "#6cf8bb",
                        "secondary-fixed": "#6ffbbe",
                        "on-secondary-container": "#00714d",
                        "tertiary": "#545c72",
                        "background": "#f8f9ff",
                        "surface": "#f8f9ff",
                        "on-surface": "#0b1c30",
                        "on-surface-variant": "#3d4a42",
                        "surface-container": "#eff4ff",
                        "surface-container-low": "#eff4ff",
                        "surface-container-high": "#dce9ff",
                        "surface-container-lowest": "#ffffff",
                        "outline": "#6d7a72",
                        "outline-variant": "#bccac0",
                        "error": "#ba1a1a"
                    },
                    fontFamily: {
                        sans: ["Inter", "-apple-system", "BlinkMacSystemFont", "Segoe UI", "Roboto", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: 20px;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>

    @if(isset($head))
        {{ $head }}
    @endif
</head>

<body class="bg-[#f8f9ff] text-[#0b1c30] min-h-full flex flex-col font-sans antialiased selection:bg-[#006948] selection:text-white">

    <!-- SHARED TOP NAVBAR -->
    <x-public.header
        :activeTab="$activeTab"
        :version="$version"
        :termsUrl="$termsUrl"
        :versionsUrl="$versionsUrl"
        :contactUrl="$contactUrl"
        :googleAuthUrl="$googleAuthUrl"
    />

    <!-- MAIN PAGE CONTENT -->
    {{ $slot }}

    <!-- SHARED FOOTER -->
    <x-public.footer
        :version="$version"
        :termsUrl="$termsUrl"
        :versionsUrl="$versionsUrl"
        :contactUrl="$contactUrl"
    />

    <!-- AUTH MODAL -->
    <x-public.auth-modal
        :googleAuthUrl="$googleAuthUrl"
        :termsUrl="$termsUrl"
    />

    <!-- GO TO TOP BUTTON -->
    <x-public.go-to-top />

    <!-- PUBLIC PAGE LOADING & SUBMIT OVERLAY -->
    <x-public.loading />

    @if(isset($scripts))
        {{ $scripts }}
    @endif
</body>
</html>
