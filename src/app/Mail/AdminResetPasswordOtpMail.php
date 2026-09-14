<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\AdminAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param AdminAccount $admin Admin account instance.
     * @param string $otp 6-digit OTP code.
     * @param int $validMinutes Validity duration in minutes.
     */
    public function __construct(
        public readonly AdminAccount $admin,
        public readonly string $otp,
        public readonly int $validMinutes = 15,
    ) {
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name', 'DrinkFlow') . '] ' . __('admin.email_otp_subject', ['app' => config('app.name', 'DrinkFlow')]),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-reset-password-otp',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
