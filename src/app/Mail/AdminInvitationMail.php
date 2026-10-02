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
 * Invitation sent to an Agent a Superadmin created: the account is pending until the invited person
 * opens the activation link and chooses its sign-in value.
 */
class AdminInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Admin $admin Pending Agent.
     * @param string $activationUrl Signed activation URL.
     * @param int $validHours Hours the link stays valid.
     * @param string $packageName Package the Agent will subscribe to once activated.
     * @param string|null $inviterName Name of the inviting Superadmin.
     */
    public function __construct(
        public readonly Admin $admin,
        public readonly string $activationUrl,
        public readonly int $validHours,
        public readonly string $packageName,
        public readonly ?string $inviterName = null,
    ) {
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('platform.emails.invitation_subject', ['app' => config('app.name', 'DrinkFlow')]));
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(view: 'emails.platform.invitation');
    }
}
