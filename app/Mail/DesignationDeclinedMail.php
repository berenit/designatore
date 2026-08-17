<?php

namespace App\Mail;

use App\Models\Designation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DesignationDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Designation $designation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Designazione rifiutata — {$this->designation->match->label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.designation-declined',
        );
    }
}
