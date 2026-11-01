<?php

use App\Mail\DesignationNotificationMail;
use App\Mail\MatchUpdatedMail;
use App\Models\User;

test('emails requesting confirmation warn that date and time are only indicative', function (string $mailable) {
    $designation = createDesignation(User::factory()->create());

    (new $mailable($designation))
        ->assertSeeInHtml('sono indicativi')
        ->assertSeeInHtml('Valgono ufficialmente sempre quelli comunicati dalla Federazione tramite il Comitato Regionale di competenza.');
})->with([
    'designation notification' => [DesignationNotificationMail::class],
    'match updated' => [MatchUpdatedMail::class],
]);
