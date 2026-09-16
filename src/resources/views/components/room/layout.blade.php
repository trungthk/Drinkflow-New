@props([
    'title' => 'DrinkFlow - ' . __('room.dashboard.page_title'),
    'room' => null,
    'roomUser' => null,
    'user' => null,
    'activeTab' => 'overview',
    'breadcrumbs' => [],
    'activeCampaign' => null,
    'unreadNotificationsCount' => 0,
    'userRooms' => collect(),
])

<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#006948",
            "primary-dark": "#005137",
            "primary-light": "#059669",
            "surface": "#F8FAFC",
            "card": "#FFFFFF",
          },
          fontFamily: {
            sans: ["Inter", "-apple-system", "BlinkMacSystemFont", "Segoe UI", "Roboto", "sans-serif"]
          },
          boxShadow: {
            "2xs": "0 1px 2px 0 rgba(0, 0, 0, 0.03)",
            "xs": "0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05)",
          }
        },
      },
    }
  </script>
  <script src="{{ rtrim(config('services.realtime.public_url', 'http://localhost:3001'), '/') }}/socket.io/socket.io.js"></script>
  @if (file_exists(public_path('build/manifest.json')) || app()->isLocal())
    @vite(['resources/css/room.css', 'resources/js/room.js'])
  @endif
  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body data-submit-loading-text="{{ __('global.common.loading') }}" class="bg-[#F8FAFC] text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900"
      data-room-socket-token-url="{{ $room ? route('user.socket-token', $room) : '' }}"
      data-realtime-url="{{ config('services.realtime.public_url', 'http://localhost:3001') }}">

  <!-- Shared Room Header -->
  <x-room.header
    :room="$room"
    :room-user="$roomUser"
    :user="$user"
    :active-tab="$activeTab"
    :breadcrumbs="$breadcrumbs"
    :active-campaign="$activeCampaign"
    :unread-notifications-count="$unreadNotificationsCount"
    :notifications="$notifications ?? null"
    :user-rooms="$userRooms"
  />

  <!-- Main Slot Content -->
  <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 space-y-6">
    {{ $slot }}
  </main>

  <!-- Shared Room Footer -->
  <x-room.footer :room="$room" />

  <!-- Shared Global Logout Modal -->
  <x-global.logout-modal />

  <!-- Shared Global Page Loading & Submit Overlay -->
  <x-global.loading />

  <!-- Shared Global Go To Top Floating Button -->
  <x-global.go-to-top />

  {{ $scripts ?? '' }}
</body>
</html>
