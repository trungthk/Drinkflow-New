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
 * Sent to an Agent right after it activated its account (queued after the activation commits).
 */
class AdminActivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Admin $admin Activated Agent.
     * @param string $packageName Name of the subscribed package.
     * @param int $roomLimit Room quota of the subscription.
     */
    public function __construct(
        public readonly Admin $admin,
        public readonly string $packageName,
        public readonly int $roomLimit,
    ) {
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('platform.emails.activated_subject', ['app' => config('app.name', 'DrinkFlow')]));
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(view: 'emails.platform.activated');
    }
}
