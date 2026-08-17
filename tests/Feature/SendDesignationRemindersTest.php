<?php

use App\Mail\DesignationNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('reminds referees who have not responded within 24 hours of the initial email', function () {
    Mail::fake();

    $designatore = User::factory()->create();
    $designation = createDesignation($designatore, [
        'created_at' => now()->subHours(25),
    ]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($designation->referee->email)
    );

    expect($designation->fresh()->reminder_sent_at)->not->toBeNull();
});

test('does not remind referees within the first 24 hours', function () {
    Mail::fake();

    $designatore = User::factory()->create();
    createDesignation($designatore, [
        'created_at' => now()->subHours(2),
    ]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertNotSent(DesignationNotificationMail::class);
});

test('does not remind designations that are already confirmed or cancelled', function () {
    Mail::fake();

    $designatore = User::factory()->create();
    createDesignation($designatore, [
        'created_at' => now()->subHours(25),
        'status' => 'confirmed',
    ]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertNotSent(DesignationNotificationMail::class);
});

test('sends a further reminder 24 hours after the previous one', function () {
    Mail::fake();

    $designatore = User::factory()->create();
    $designation = createDesignation($designatore, [
        'created_at' => now()->subHours(50),
        'reminder_sent_at' => now()->subHours(25),
    ]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => $mail->designation->id === $designation->id);
});
