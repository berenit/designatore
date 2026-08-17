<?php

use App\Mail\DesignationNotificationMail;
use App\Mail\DesignationRemovedMail;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('changing the assigned referee notifies the old one and the new one', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user);
    $oldReferee = $designation->referee;

    $newReferee = Referee::create([
        'name' => 'Nuovo Arbitro',
        'email' => 'nuovo-arbitro@example.com',
    ]);

    $this->actingAs($user)
        ->put(route('designations.update', $designation), [
            'match_id' => $designation->match_id,
            'referee_id' => $newReferee->id,
            'role' => $designation->role,
            'status' => $designation->status,
        ])
        ->assertRedirect(route('designations.index'));

    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($oldReferee->email)
    );

    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($newReferee->email)
    );

    expect($designation->fresh()->referee_id)->toBe($newReferee->id);
});

test('keeping the same referee on update does not send any email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user);

    $this->actingAs($user)
        ->put(route('designations.update', $designation), [
            'match_id' => $designation->match_id,
            'referee_id' => $designation->referee_id,
            'role' => $designation->role,
            'status' => 'confirmed',
        ])
        ->assertRedirect(route('designations.index'));

    Mail::assertNotSent(DesignationRemovedMail::class);
    Mail::assertNotSent(DesignationNotificationMail::class);
});

test('reassigning the referee via the match designation form notifies the old one and the new one', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user);
    $oldReferee = $designation->referee;
    $match = $designation->match;

    $newReferee = Referee::create([
        'name' => 'Nuovo Arbitro',
        'email' => 'nuovo-arbitro-store@example.com',
    ]);

    $this->actingAs($user)
        ->post(route('designations.store'), [
            'match_id' => $match->id,
            'referees' => ['Arbitro' => $newReferee->id],
        ])
        ->assertRedirect(route('designations.index'));

    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($oldReferee->email)
    );

    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($newReferee->email)
    );

    expect($designation->fresh()->referee_id)->toBe($newReferee->id);
});

test('resubmitting the match designation form with the same referee does not send a removal email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user);
    $match = $designation->match;

    $this->actingAs($user)
        ->post(route('designations.store'), [
            'match_id' => $match->id,
            'referees' => ['Arbitro' => $designation->referee_id],
        ])
        ->assertRedirect(route('designations.index'));

    Mail::assertNotSent(DesignationRemovedMail::class);
});

test('reassigning an already cancelled designation does not notify the old referee', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user, ['status' => 'cancelled']);
    $oldReferee = $designation->referee;

    $newReferee = Referee::create([
        'name' => 'Nuovo Arbitro',
        'email' => 'nuovo-arbitro-2@example.com',
    ]);

    $this->actingAs($user)
        ->put(route('designations.update', $designation), [
            'match_id' => $designation->match_id,
            'referee_id' => $newReferee->id,
            'role' => $designation->role,
            'status' => 'pending',
        ])
        ->assertRedirect(route('designations.index'));

    Mail::assertNotSent(DesignationRemovedMail::class, fn ($mail) => $mail->hasTo($oldReferee->email));

    Mail::assertSent(DesignationNotificationMail::class, fn ($mail) => $mail->hasTo($newReferee->email));
});
