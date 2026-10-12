<?php

use App\Mail\DesignationNotificationMail;
use App\Mail\DesignationUnansweredMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

/** Designazione in attesa la cui richiesta di conferma è partita $hoursAgo ore fa. */
function notifiedDesignation(User $designatore, float $hoursAgo, array $attributes = []): Designation
{
    return createDesignation($designatore, $attributes + [
        'notified_at' => now()->subMinutes((int) round($hoursAgo * 60)),
        'reminders_sent' => 0,
    ]);
}

test('sends SOLLECITO 1 eight hours after the confirmation request', function () {
    $designation = notifiedDesignation(User::factory()->create(), 8.1);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($designation->referee->email)
        && str_starts_with($mail->envelope()->subject, 'SOLLECITO 1: Designazione arbitrale — ')
    );

    expect($designation->fresh()->reminders_sent)->toBe(1);
});

test('does not remind before eight hours or twice for the same step', function () {
    notifiedDesignation(User::factory()->create(), 7.9);
    notifiedDesignation(User::factory()->create(), 9, ['reminders_sent' => 1]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('numbers the reminders every eight hours and marks the fifth as the last one', function (float $hoursAgo, int $alreadySent, string $prefix) {
    notifiedDesignation(User::factory()->create(), $hoursAgo, ['reminders_sent' => $alreadySent]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertSent(DesignationNotificationMail::class, 1);
    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => str_starts_with($mail->envelope()->subject, "{$prefix}: Designazione arbitrale — "));
})->with([
    'secondo sollecito' => [16.1, 1, 'SOLLECITO 2'],
    'terzo sollecito' => [24.1, 2, 'SOLLECITO 3'],
    'quarto sollecito' => [32.1, 3, 'SOLLECITO 4'],
    'ultimo sollecito' => [40.1, 4, 'ULTIMO SOLLECITO'],
]);

test('after the last reminder nothing else is sent to the referee before 48 hours', function () {
    notifiedDesignation(User::factory()->create(), 47.9, ['reminders_sent' => 5]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('after 48 hours without an answer the designatore is notified once', function () {
    $designatore = User::factory()->create();
    $designation = notifiedDesignation($designatore, 48.1, ['reminders_sent' => 5]);

    $this->artisan('designations:send-reminders')->assertSuccessful();
    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertSent(DesignationUnansweredMail::class, 1);
    Mail::assertSent(DesignationUnansweredMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($designatore->email)
    );
    Mail::assertNotSent(DesignationNotificationMail::class);

    expect($designation->fresh()->escalated_at)->not->toBeNull()
        ->and($designation->fresh()->status)->toBe('pending');
});

test('if the scheduler was down only the latest due reminder is sent', function () {
    $designation = notifiedDesignation(User::factory()->create(), 25);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertSent(DesignationNotificationMail::class, 1);
    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => str_starts_with($mail->envelope()->subject, 'SOLLECITO 3: '));
    expect($designation->fresh()->reminders_sent)->toBe(3);
});

test('does not remind answered designations or requests never actually sent', function () {
    $designatore = User::factory()->create();
    notifiedDesignation($designatore, 9, ['status' => 'confirmed']);
    notifiedDesignation($designatore, 49, ['status' => 'cancelled']);
    createDesignation($designatore, ['notified_at' => null, 'created_at' => now()->subHours(50)]);

    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('the reminder cycle starts when the confirmation email is actually sent', function () {
    $designatore = User::factory()->create();
    $designation = createDesignation($designatore, ['notified_at' => null]);
    $newReferee = Referee::create(['name' => 'Nuovo Arbitro', 'email' => 'nuovo-solleciti@example.com']);

    $this->actingAs($designatore)->put(route('designations.update', $designation), [
        'match_id' => $designation->match_id,
        'referee_id' => $newReferee->id,
        'role' => $designation->role,
        'status' => 'pending',
    ]);

    // Email ancora in attesa di conferma: nessun sollecito previsto
    expect($designation->fresh()->notified_at)->toBeNull();

    $this->actingAs($designatore)->post(route('pending-mails.send'));

    expect($designation->fresh()->notified_at)->not->toBeNull()
        ->and($designation->fresh()->reminders_sent)->toBe(0);
});

test('discarding the confirmation email means no reminders', function () {
    $designatore = User::factory()->create();
    $designation = createDesignation($designatore, ['notified_at' => now()->subHours(30), 'reminders_sent' => 3]);
    $newReferee = Referee::create(['name' => 'Nuovo Arbitro', 'email' => 'nuovo-solleciti-2@example.com']);

    $this->actingAs($designatore)->put(route('designations.update', $designation), [
        'match_id' => $designation->match_id,
        'referee_id' => $newReferee->id,
        'role' => $designation->role,
        'status' => 'pending',
    ]);
    $this->actingAs($designatore)->post(route('pending-mails.discard'));

    $this->travel(50)->hours();
    $this->artisan('designations:send-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('reminder and designatore emails render with the expected wording', function () {
    $designation = notifiedDesignation(User::factory()->create(), 48.1, ['reminders_sent' => 5]);

    expect((new DesignationNotificationMail($designation, 'ULTIMO SOLLECITO'))->render())
        ->toContain('Ultimo sollecito:')
        ->toContain('non abbiamo ancora ricevuto la tua risposta');

    expect((new DesignationNotificationMail($designation))->render())
        ->not->toContain('non abbiamo ancora ricevuto la tua risposta');

    expect((new DesignationUnansweredMail($designation))->render())
        ->toContain('Designazione senza risposta')
        ->toContain('nonostante i 5 solleciti inviati')
        ->toContain($designation->referee->name);
});

test('when the designatore marks a designation as accepted, reminders stop and the email buttons no longer work', function () {
    $designatore = User::factory()->create();
    $designation = notifiedDesignation($designatore, 9);

    $this->actingAs($designatore)->put(route('designations.update', $designation), [
        'match_id' => $designation->match_id,
        'referee_id' => $designation->referee_id,
        'role' => $designation->role,
        'status' => 'confirmed',
    ]);

    // Nessun sollecito né avviso al designatore, nemmeno oltre le 48 ore
    $this->artisan('designations:send-reminders')->assertSuccessful();
    $this->travel(50)->hours();
    $this->artisan('designations:send-reminders')->assertSuccessful();
    Mail::assertNothingSent();

    // I pulsanti dell'email (anche "Rifiuta") non cambiano più lo stato
    $this->travelBack();
    $this->get((new DesignationNotificationMail($designation))->declineUrl)
        ->assertOk()
        ->assertSee('Designazione già elaborata');

    expect($designation->fresh()->status)->toBe('confirmed');
});
