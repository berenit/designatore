<?php

namespace App\Mail;

use App\Models\Designation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DesignationRemovedMail extends Mailable
{
    // Niente SerializesModels: l'email viene serializzata per intero mentre attende conferma
    // (vedi PendingMails), anche quando la designazione/partita nel frattempo viene eliminata.
    use Queueable;

    public function __construct(public Designation $designation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Designazione rimossa — {$this->designation->match->label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.designation-removed',
        );
    }
}
