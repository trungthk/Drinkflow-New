<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.email_otp_subject') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 560px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #006948 0%, #047857 100%);
            padding: 28px 32px;
            text-align: center;
            color: #ffffff;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .brand-subtitle {
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #a7f3d0;
            margin-top: 4px;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .desc {
            font-size: 14px;
            color: #475569;
            margin-bottom: 24px;
        }
        .otp-box {
            background: #f0fdf4;
            border: 2px dashed #059669;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #006948;
            margin: 0;
        }
        .otp-validity {
            font-size: 12px;
            color: #047857;
            margin-top: 8px;
            font-weight: 600;
        }
        .warning-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 14px;
            border-radius: 6px;
            margin-top: 24px;
            font-size: 12px;
            color: #92400e;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 32px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="brand-title">DrinkFlow Operations</h1>
            <div class="brand-subtitle">Administrator Security Authentication</div>
        </div>

        <div class="content">
            <div class="greeting">{{ __('admin.email_hello', ['name' => $admin->name ?? $admin->email]) }}</div>
            <div class="desc">
                {{ __('admin.email_otp_instructions', ['app' => config('app.name', 'DrinkFlow')]) }}
            </div>

            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-validity">{{ __('admin.email_otp_validity', ['minutes' => $validMinutes]) }}</div>
            </div>

            <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                {{ __('admin.email_otp_subtext') }}
            </p>

            <div class="warning-box">
                <strong>{{ __('admin.email_security_notice') }}:</strong>
                {{ __('admin.email_security_desc') }}
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 4px;">&copy; {{ date('Y') }} {{ config('app.name', 'DrinkFlow') }}. All rights reserved.</p>
            <p style="margin: 0; font-size: 10px; color: #94a3b8;">{{ __('admin.email_system_generated_msg') }}</p>
        </div>
    </div>
</body>
</html>
