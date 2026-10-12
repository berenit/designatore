<?php

namespace App\Mail;

use App\Models\Designation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Avvisa il designatore che l'arbitro non ha risposto entro la scadenza, nonostante i solleciti. */
class DesignationUnansweredMail extends Mailable
{
    use Queueable;

    public function __construct(public Designation $designation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Designazione senza risposta — {$this->designation->match->label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.designation-unanswered',
        );
    }
}
