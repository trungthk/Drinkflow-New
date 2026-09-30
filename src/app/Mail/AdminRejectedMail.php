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
 * Sent to an Agent whose registration was rejected, with the reason (queued after the rejection commits).
 */
class AdminRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Admin $admin Rejected Agent.
     * @param string $reason Rejection reason written by the Superadmin.
     */
    public function __construct(
        public readonly Admin $admin,
        public readonly string $reason,
    ) {
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('platform.emails.rejected_subject', ['app' => config('app.name', 'DrinkFlow')]));
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(view: 'emails.platform.rejected');
    }
}
