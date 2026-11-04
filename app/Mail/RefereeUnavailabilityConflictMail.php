<?php

namespace App\Mail;

use App\Models\Referee;
use App\Models\RefereeUnavailability;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class RefereeUnavailabilityConflictMail extends Mailable
{
    public function __construct(
        public Referee $referee,
        public RefereeUnavailability $period,
        public Collection $designations,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Indisponibilità su gare già designate — {$this->referee->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.referee-unavailability-conflict',
        );
    }
}
