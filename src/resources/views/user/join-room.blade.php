@extends('user.layout')

@section('title', 'Tham gia ' . $room->name . ' · DrinkFlow')
@section('content')
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-4">
        <section class="w-full rounded-3xl bg-white p-8 text-center shadow-sm">
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white">
                D</div>
            <h1 class="mt-5 text-2xl font-semibold">{{ $room->name }}</h1>
            <p class="mt-2 text-slate-600">
                {{ $room->description ?: 'Tham gia Room để xem campaign và đặt món cùng mọi người.' }}</p>
            @if ($user)
                <div class="mt-6 rounded-xl bg-slate-50 p-4 text-left">
                    <p class="text-sm text-slate-500">Tài khoản công ty</p>
                    <p class="mt-1 font-medium">{{ $user->name }}</p>
                    <p class="text-sm text-slate-600">{{ $user->email }}</p>
                </div>
                <form class="mt-5" method="post" action="{{ route('user.rooms.join', $room) }}">@csrf<button
                        class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-medium text-white hover:bg-indigo-700">Tham
                    gia Room</button></form>@else<a
                    class="mt-6 block w-full rounded-xl bg-indigo-600 px-4 py-3 font-medium text-white hover:bg-indigo-700"
                    href="{{ route('auth.google') }}">Tiếp tục với Google</a>
                <p class="mt-4 text-xs text-slate-500">Sử dụng email Google Workspace thuộc domain công ty.</p>
            @endif
        </section>
    </main>
@endsection
