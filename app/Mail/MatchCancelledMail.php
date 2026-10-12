<?php

namespace App\Mail;

use App\Models\Designation;
use App\Models\RugbyMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MatchCancelledMail extends Mailable
{
    // Niente SerializesModels: l'email viene serializzata per intero mentre attende conferma
    // (vedi PendingMails), anche quando la designazione/partita nel frattempo viene eliminata.
    use Queueable;

    public function __construct(public RugbyMatch $match, public Designation $designation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Partita annullata — {$this->match->label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.match-cancelled',
        );
    }
}
