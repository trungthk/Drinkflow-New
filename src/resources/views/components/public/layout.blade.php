@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'ogTitle' => null,
    'ogDescription' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'canonicalUrl' => null,
    'activeTab' => 'about',
    'version' => null,
    'termsUrl' => null,
    'versionsUrl' => null,
    'contactUrl' => null,
    'googleAuthUrl' => null,
])

<!DOCTYPE html>
<html class="light h-full" lang="{{ $locale }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Primary SEO / Metadata -->
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}"/>
    <meta name="keywords" content="{{ $pageKeywords }}"/>
    <meta name="author" content="DrinkFlow"/>
    <meta name="robots" content="index, follow"/>
    <link rel="canonical" href="{{ $currentUrl }}"/>

    <!-- Open Graph / Facebook / Zalo / LinkedIn -->
    <meta property="og:site_name" content="DrinkFlow"/>
    <meta property="og:type" content="{{ $ogType }}"/>
    <meta property="og:url" content="{{ $currentUrl }}"/>
    <meta property="og:title" content="{{ $pageOgTitle }}"/>
    <meta property="og:description" content="{{ $pageOgDescription }}"/>
    <meta property="og:image" content="{{ $pageOgImage }}"/>
    <meta property="og:locale" content="{{ $ogLocale }}"/>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image"/>
    <meta name="twitter:url" content="{{ $currentUrl }}"/>
    <meta name="twitter:title" content="{{ $pageOgTitle }}"/>
    <meta name="twitter:description" content="{{ $pageOgDescription }}"/>
    <meta name="twitter:image" content="{{ $pageOgImage }}"/>

    <!-- Schema.org JSON-LD Structured Data for Rich Search Results -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebApplication",
      "name": "DrinkFlow",
      "url": "{{ url('/') }}",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "All",
      "description": "{{ $pageDescription }}",
      "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "VND"
      }
    }
    </script>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin=""/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block" rel="stylesheet"/>

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

    {{-- Socket.IO client (window.io): public pages only listen for maintenance notices. Deferred scripts still
         run before DOMContentLoaded, when public.js connects. --}}
    <script defer src="{{ rtrim(config('services.realtime.public_url', 'http://localhost:3001'), '/') }}/socket.io/socket.io.js"></script>
    @if (file_exists(public_path('build/manifest.json')) || app()->isLocal())
        @vite(['resources/css/public.css', 'resources/js/public.js'])
    @endif

    @if(isset($head))
        {{ $head }}
    @endif
</head>

<body data-submit-loading-text="{{ __('global.common.loading') }}" data-realtime-url="{{ rtrim(config('services.realtime.public_url', 'http://localhost:3001'), '/') }}" @if (app()->isLocal()) data-socket-debug @endif class="bg-[#f8f9ff] text-[#0b1c30] min-h-full flex flex-col font-sans antialiased selection:bg-[#006948] selection:text-white">

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
        :activeTab="$activeTab"
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

    <!-- FLOATING ACTIONS: CONTACT & GO TO TOP BUTTON -->
    <x-public.go-to-top />

    <!-- PUBLIC PAGE LOADING & SUBMIT OVERLAY -->
    <x-public.loading />

    @if(isset($scripts))
        {{ $scripts }}
    @endif
</body>
</html>
