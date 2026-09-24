<?php

declare(strict_types=1);

namespace App\Services\System;

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
     * Send a real test email to actively verify the mail transport.
     *
     * The optional message is plain text typed by the superadmin: it is HTML-escaped and only its
     * line breaks are kept, so it can never inject markup into the email.
     *
     * @param string $recipient Validated email address to send the test message to.
     * @param string|null $message Optional custom body; the default test sentence is used when empty.
     * @return bool True when the mailer accepted the message without throwing.
     */
    public function sendTest(string $recipient, ?string $message = null): bool
    {
        $message = trim((string) $message);
        $body = $message !== ''
            ? nl2br(e($message), false)
            : e(__('superadmin.system.mail_test_body', ['app' => config('app.name')]));

        try {
            Mail::html(
                '<p>'.$body.'</p>',
                function (Message $mail) use ($recipient): void {
                    $mail->to($recipient)
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
