<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        'allowed_domains' => array_values(array_filter(array_map('strtolower', array_map('trim', explode(',', (string) env('GOOGLE_ALLOWED_DOMAINS', '')))))),
    ],

    'realtime' => [
        'url' => env('REALTIME_URL'),
        'public_url' => env('REALTIME_PUBLIC_URL', 'http://localhost:3001'),
        'internal_secret' => env('REALTIME_INTERNAL_SECRET'),
        'socket_token_secret' => env('SOCKET_TOKEN_SECRET', env('APP_KEY')),
    ],

    // Only used by App\Services\System\SupervisorHealthService on the superadmin monitoring
    // dashboard. Left disabled unless a server actually runs `php artisan queue:work` under
    // Supervisor and sets these — there is nothing to query on a local dev machine.
    'supervisor' => [
        'enabled' => (bool) env('SUPERVISOR_ENABLED', false),
        'program' => env('SUPERVISOR_PROGRAM'),
        'supervisorctl_path' => env('SUPERVISORCTL_PATH', '/usr/bin/supervisorctl'),
    ],

];
