<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email verification link sent to a self-registered Agent.
 */
class AdminEmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Admin $admin Pending Agent.
     * @param string $verificationUrl Signed verification URL.
     * @param int $validHours Hours the link stays valid.
     */
    public function __construct(
        public readonly Admin $admin,
        public readonly string $verificationUrl,
        public readonly int $validHours,
    ) {
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('platform.emails.verify_subject', ['app' => config('app.name', 'DrinkFlow')]));
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(view: 'emails.platform.verify-email');
    }
}
