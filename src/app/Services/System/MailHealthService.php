<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\AdminAccount;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Reports whether outbound mail is configured, and can send a one-off test message on demand.
 */
class MailHealthService
{
    /**
     * Passive check: is a real transport configured, not just the dev "log"/"array" driver.
     *
     * Never opens a network connection — safe to call on every dashboard load.
     *
     * @return array{driver: string, configured: bool}
     */
    public function check(): array
    {
        $driver = (string) config('mail.default', 'log');
        $devDrivers = ['log', 'array'];

        return [
            'driver' => $driver,
            'configured' => ! in_array($driver, $devDrivers, true),
        ];
    }

    /**
     * Send a real test email to the given administrator to actively verify the mail transport.
     *
     * @param AdminAccount $admin Administrator who requested the test (also the recipient).
     * @return bool True when the mailer accepted the message without throwing.
     */
    public function sendTest(AdminAccount $admin): bool
    {
        try {
            Mail::html(
                '<p>'.__('superadmin.system.mail_test_body', ['app' => config('app.name')]).'</p>',
                function (Message $message) use ($admin): void {
                    $message->to($admin->email)
                        ->subject(__('superadmin.system.mail_test_subject'));
                }
            );

            return true;
        } catch (Throwable $e) {
            Log::warning('Superadmin mail health test failed.', ['exception' => $e::class, 'message' => $e->getMessage()]);

            return false;
        }
    }
}
