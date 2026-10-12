<?php

namespace App\Mail;

use App\Models\Designation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class DesignationNotificationMail extends Mailable implements RequestsConfirmation
{
    // Niente SerializesModels: l'email viene serializzata per intero mentre attende conferma
    // (vedi PendingMails), anche quando la designazione/partita nel frattempo viene eliminata.
    use Queueable;

    public string $acceptUrl;

    public string $declineUrl;

    /** $reminderLabel: prefisso dell'oggetto per i solleciti ("SOLLECITO 1", ..., "ULTIMO SOLLECITO"); null per il primo invio. */
    public function __construct(public Designation $designation, public ?string $reminderLabel = null)
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

    public function confirmationDesignation(): Designation
    {
        return $this->designation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->reminderLabel ? "{$this->reminderLabel}: " : '')."Designazione arbitrale — {$this->designation->match->label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.designation-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
