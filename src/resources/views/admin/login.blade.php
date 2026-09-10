<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập Quản trị viên DrinkFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen items-center justify-center bg-[#f8f9ff] p-4 text-[#0b1c30] sm:p-8">
    <main class="w-full max-w-[640px] overflow-hidden rounded-2xl bg-white shadow-[0_18px_45px_rgba(11,28,48,.12)]">
        <div class="h-1.5 bg-[#0b1c30]"></div>
        <div class="p-6 sm:p-10">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-xl bg-[#0b1c30] text-white"><span
                            class="material-symbols-outlined">local_cafe</span></div>
                    <div><strong class="block text-lg tracking-tight">DrinkFlow</strong><span
                            class="text-xs font-semibold uppercase tracking-wider text-slate-500">Enterprise
                            Edition</span></div>
                </div>
                <span
                    class="hidden items-center gap-1 rounded-full bg-[#eff4ff] px-3 py-2 text-xs font-semibold text-[#006c49] sm:inline-flex"><span
                        class="h-2 w-2 rounded-full bg-[#00875a]"></span>Phân hệ Quản trị Room</span>
            </div>
            <h1 class="mt-8 text-3xl font-bold tracking-tight sm:text-4xl">Đăng nhập Quản trị viên DrinkFlow</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Quản lý chiến dịch gom đơn, đối soát VietQR và phân bổ ngân
                sách phòng ban.</p>
            <div class="mt-6 flex gap-3 rounded-xl bg-[#eff4ff] p-4"><span
                    class="material-symbols-outlined text-[#00875a]">verified_user</span>
                <div><strong class="block text-sm text-[#006c49]">Phạm vi phân quyền độc lập</strong>
                    <p class="mt-1 text-xs leading-5 text-slate-600">Admin chỉ quản lý các Room được phân công (Assigned
                        Rooms).</p>
                </div>
            </div>
            @if ($errors->any())
                <div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('admin.login') }}" class="mt-7 space-y-5">@csrf
                <label class="block text-sm font-semibold">Email công vụ (@company.com)<span
                        class="float-right font-normal text-slate-500">SSO / Local ID</span><span
                        class="mt-2 flex items-center rounded-xl border border-slate-200 bg-white px-3"><span
                            class="material-symbols-outlined text-slate-500">alternate_email</span><input name="email"
                            type="email" value="{{ old('email') }}" required
                            class="w-full border-0 px-3 py-3 text-sm outline-none focus:ring-0"
                            placeholder="admin@company.com"></span></label>
                <label class="block text-sm font-semibold">Mật khẩu xác thực<a href="#"
                        class="float-right font-normal text-[#006c49] no-underline">Quên mật khẩu?</a><span
                        class="mt-2 flex items-center rounded-xl border border-slate-200 bg-white px-3"><span
                            class="material-symbols-outlined text-slate-500">lock</span><input id="admin-password"
                            name="password" type="password" required
                            class="w-full border-0 px-3 py-3 text-sm outline-none focus:ring-0"
                            placeholder="••••••••••••"><button id="toggle-admin-password" type="button"
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-[#0b1c30]"
                            aria-label="Hiện mật khẩu"><span
                                class="material-symbols-outlined text-[19px]">visibility</span></button></span></label>
                <div class="rounded-xl bg-[#dce9ff] p-4">
                    <div class="flex items-center justify-between text-xs font-semibold"><span><span
                                class="material-symbols-outlined align-middle text-[15px]">shield</span> Xác thực
                            Captcha chống bot</span><span class="text-[#006c49]">Level 2 Active</span></div>
                    <div class="mt-3 flex gap-2">
                        <div
                            class="flex flex-1 items-center justify-center rounded-lg bg-[#c7dbff] px-3 text-lg font-bold tracking-[.35em]">
                            {{ $captchaQuestion }}</div><input name="captcha" inputmode="numeric" required
                            class="w-40 rounded-lg border-0 px-3 text-sm outline-none" placeholder="Nhập đáp án">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input name="remember" type="checkbox"
                        value="1" class="rounded"> Duy trì phiên làm việc an toàn 24h <span
                        class="ml-auto text-xs text-slate-500"><span
                            class="material-symbols-outlined align-middle text-[14px]">shield_lock</span> HTTP-only
                        Session</span></label>
                <button
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-black px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-[#213145]"
                    type="submit"><span class="material-symbols-outlined">login</span>Đăng nhập Quản trị Room</button>
            </form>
            <div class="mt-6 flex gap-3 rounded-xl bg-[#dce9ff] p-4 text-xs leading-5 text-slate-600"><span
                    class="material-symbols-outlined text-slate-500">admin_panel_settings</span>
                <p><strong class="text-[#0b1c30]">Cơ chế rate limiting</strong><span class="ml-2 text-[#006c49]">● 5/5
                        lần thử khả dụng</span><br>Khóa tạm thời sau 5 lần thất bại liên tiếp. Nhật ký đăng nhập kèm IP
                    và thiết bị được ghi vào Audit Trail.</p>
            </div>
            <p class="mt-7 text-center text-xs text-slate-500">Gặp sự cố truy cập? Liên hệ Service Desk · <span
                    class="font-semibold text-[#006c49]">IT Ext: 8844</span></p>
        </div>
    </main>
    <script>
        const passwordInput = document.querySelector('#admin-password');
        const passwordToggle = document.querySelector('#toggle-admin-password');
        passwordToggle?.addEventListener('click', () => {
            const visible = passwordInput.type === 'text';
            passwordInput.type = visible ? 'password' : 'text';
            passwordToggle.setAttribute('aria-label', visible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            passwordToggle.querySelector('.material-symbols-outlined').textContent = visible ? 'visibility' :
                'visibility_off';
        });
        document.querySelector('form[action="{{ route('admin.login') }}"]')?.addEventListener('submit', event => {
            const submit = event.currentTarget.querySelector('button[type="submit"]');
            if (!submit) return;
            submit.disabled = true;
            submit.classList.add('cursor-wait', 'opacity-70');
            submit.innerHTML =
                '<span class="material-symbols-outlined animate-spin">progress_activity</span>Đang xác thực...';
        });
    </script>
</body>

</html>
