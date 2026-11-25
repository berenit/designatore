<?php

use App\Mail\DesignationDeclinedMail;
use App\Mail\DesignationNotificationMail;
use App\Mail\MatchUpdatedMail;
use App\Models\User;
use App\Support\MailTable;

test('mail table cells flatten newlines and escape pipes', function () {
    expect(MailTable::cell("Riga uno\r\n  riga | due\n"))->toBe('Riga uno riga \| due')
        ->and(MailTable::cell(null))->toBe('');
});

test('multi-line notes stay inside the designation table', function (string $mailable) {
    $designation = createDesignation(User::factory()->create());
    $designation->update(['notes' => "Ritrovo alle 14 | portare\nil fischietto"]);

    (new $mailable($designation->fresh()))
        ->assertSeeInHtml('Ritrovo alle 14 | portare il fischietto</td>', false);
})->with([
    'designation notification' => [DesignationNotificationMail::class],
    'match updated' => [MatchUpdatedMail::class],
    'designation declined' => [DesignationDeclinedMail::class],
]);
