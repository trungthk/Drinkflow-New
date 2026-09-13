<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.select_room_title') }} · DrinkFlow Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>

<body class="superadmin-shell">
    <div class="superadmin-main !pl-0">
        <header class="superadmin-topbar">
            <div class="superadmin-profile !ml-0">
                <div class="superadmin-logo"><span class="material-symbols-outlined">local_cafe</span></div>
                <div><strong>{{ __('admin.brand_title') }}</strong><small>{{ __('admin.brand_subtitle') }}</small></div>
            </div>
            <form method="post" action="{{ route('admin.logout') }}" class="ml-auto">
                @csrf
                <button class="sa-button" type="submit">
                    <span class="material-symbols-outlined">logout</span>{{ __('admin.logout') }}
                </button>
            </form>
        </header>
        <main class="superadmin-content">
            <div class="superadmin-heading">
                <div>
                    <p class="superadmin-eyebrow">Admin Gateway · Assigned Rooms</p>
                    <h1>{{ __('admin.select_room_heading') }}</h1>
                    <p>{{ __('admin.select_room_subheading') }}</p>
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse($rooms as $room)
                    <a href="{{ route('admin.dashboard.page', $room) }}"
                        class="sa-card block p-6 no-underline transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start justify-between gap-3">
                            <div class="grid h-11 w-11 place-items-center rounded-xl bg-[#eff4ff] text-[#0b1c30]">
                                <span class="material-symbols-outlined">domain</span>
                            </div>
                            <span class="status-pill status-active">Assigned</span>
                        </div>
                        <h2 class="mt-5 text-xl font-bold tracking-tight text-[#0b1c30]">{{ $room->name }}</h2>
                        <p class="mt-2 min-h-10 text-sm leading-6 text-slate-500">
                            {{ $room->description ?: __('admin.default_room_desc') }}
                        </p>
                        <span class="mt-6 inline-flex items-center gap-1 text-xs font-bold text-[#00875a]">
                            {{ __('admin.open_dashboard') }} <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </span>
                    </a>
                @empty
                    <div class="sa-card p-8 text-sm text-slate-500">{{ __('admin.no_assigned_rooms') }}</div>
                @endforelse
            </div>
        </main>
    </div>
</body>

</html>
