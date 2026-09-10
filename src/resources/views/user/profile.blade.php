@extends('user.layout')

@section('title', 'Profile · DrinkFlow')
@section('content')
    <main class="mx-auto max-w-3xl px-4 py-8"><a class="text-sm text-indigo-600" href="{{ route('user.rooms.index') }}">←
            Rooms của tôi</a>
        <section class="mt-5 rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4"><img id="avatar" class="h-16 w-16 rounded-full bg-slate-100"
                    alt="Avatar">
                <div>
                    <h1 id="name" class="text-2xl font-semibold">Đang tải…</h1>
                    <p id="email" class="text-slate-500"></p>
                </div>
            </div>
            <h2 class="mt-8 text-lg font-medium">Room đã tham gia</h2>
            <ul id="rooms" class="mt-3 divide-y divide-slate-100"></ul>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        Promise.all([fetch(@json(route('user.profile.show')), {
            headers: {
                Accept: 'application/json'
            }
        }).then(r => r.json()), fetch(@json(route('user.rooms.index')), {
            headers: {
                Accept: 'application/json'
            }
        }).then(r => r.json())]).then(([profile, rooms]) => {
            const u = profile.data;
            document.querySelector('#name').textContent = u.name;
            document.querySelector('#email').textContent = u.email;
            if (u.avatar_url) document.querySelector('#avatar').src = u.avatar_url;
            document.querySelector('#rooms').innerHTML = (rooms.data?.data || []).map(m =>
                `<li class="flex items-center justify-between py-3"><span>${m.room?.name||''}</span><a class="text-sm text-indigo-600" href="/rooms/${m.room?.id}/dashboard">Mở Room →</a></li>`
                ).join('') || '<li class="py-3 text-slate-500">Chưa tham gia Room nào.</li>';
        });
    </script>
@endpush
