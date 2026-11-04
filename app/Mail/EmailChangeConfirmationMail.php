<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class EmailChangeConfirmationMail extends Mailable
{
    /** Validità del link di conferma, in ore. */
    public const LINK_HOURS = 24;

    public function __construct(public User $user, public string $newEmail)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Conferma il tuo nuovo indirizzo email',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change-confirmation',
            with: [
                'url' => URL::temporarySignedRoute('profile.email.confirm', now()->addHours(self::LINK_HOURS), [
                    'user' => $this->user->id,
                    'email' => $this->newEmail,
                ]),
                'hours' => self::LINK_HOURS,
            ],
        );
    }
}
