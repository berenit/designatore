<?php

namespace App\Mail;

use App\Models\Designation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class MatchUpdatedMail extends Mailable
{
    // Niente SerializesModels: l'email viene serializzata per intero mentre attende conferma
    // (vedi PendingMails), anche quando la designazione/partita nel frattempo viene eliminata.
    use Queueable;

    public string $acceptUrl;

    public string $declineUrl;

    public function __construct(public Designation $designation)
    {
        $this->acceptUrl = URL::signedRoute('designations.respond', [
            'designation' => $designation->id,
            'action' => 'confirm',
        ]);
        $this->declineUrl = URL::signedRoute('designations.respond', [
            'designation' => $designation->id,
            'action' => 'decline',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Partita modificata — {$this->designation->match->label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.match-updated',
        );
    }
}
