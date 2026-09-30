{{-- Shared layout of the Agent account emails (verification, approval, rejection). --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; line-height: 1.6; }
        .container { max-width: 560px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #006948 0%, #047857 100%); padding: 24px 32px; text-align: center; color: #ffffff; }
        .brand-title { font-size: 22px; font-weight: 800; margin: 0; }
        .content { padding: 32px; font-size: 14px; color: #334155; }
        .greeting { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 12px; }
        .button { display: inline-block; background: #006948; color: #ffffff !important; text-decoration: none; font-weight: 700; padding: 12px 22px; border-radius: 10px; margin: 16px 0; }
        .box { background: #f1f5f9; border-radius: 12px; padding: 14px 16px; margin: 16px 0; }
        .muted { font-size: 12px; color: #64748b; word-break: break-all; }
        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 32px; text-align: center; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h1 class="brand-title">{{ config('app.name', 'DrinkFlow') }}</h1></div>
        <div class="content">
            <div class="greeting">{{ __('platform.emails.hello', ['name' => $admin->name]) }}</div>
            @yield('content')
        </div>
        <div class="footer">&copy; {{ date('Y') }} {{ config('app.name', 'DrinkFlow') }} · {{ __('platform.emails.automated') }}</div>
    </div>
</body>
</html>
