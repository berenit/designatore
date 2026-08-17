<?php

use App\Mail\DesignationDeclinedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('declining a designation notifies the designatore who assigned it by email', function () {
    Mail::fake();

    $designatore = User::factory()->create(['email' => 'designatore@example.com']);
    $designation = createDesignation($designatore);

    $url = URL::signedRoute('designations.respond', [
        'designation' => $designation->id,
        'action' => 'decline',
    ]);

    $this->get($url)->assertOk();

    expect($designation->fresh()->status)->toBe('cancelled');

    Mail::assertSent(DesignationDeclinedMail::class, function (DesignationDeclinedMail $mail) use ($designation, $designatore) {
        return $mail->designation->id === $designation->id
            && $mail->hasTo($designatore->email);
    });
});

test('confirming a designation does not notify the designatore', function () {
    Mail::fake();

    $designatore = User::factory()->create();
    $designation = createDesignation($designatore);

    $url = URL::signedRoute('designations.respond', [
        'designation' => $designation->id,
        'action' => 'confirm',
    ]);

    $this->get($url)->assertOk();

    expect($designation->fresh()->status)->toBe('confirmed');

    Mail::assertNotSent(DesignationDeclinedMail::class);
});
