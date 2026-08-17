<?php

use App\Mail\DesignationRemovedMail;
use App\Mail\MatchCancelledMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('deleting a designation notifies the referee', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user);

    $this->actingAs($user)
        ->delete(route('designations.destroy', $designation))
        ->assertRedirect(route('designations.index'));

    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($designation->referee->email)
    );
});

test('deleting an already declined designation does not notify the referee again', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user, ['status' => 'cancelled']);

    $this->actingAs($user)
        ->delete(route('designations.destroy', $designation))
        ->assertRedirect(route('designations.index'));

    Mail::assertNotSent(DesignationRemovedMail::class);
});

test('cancelling a match notifies referees with active designations', function () {
    Mail::fake();

    $user = User::factory()->create();
    $active = createDesignation($user);
    $declined = createDesignation($user, ['status' => 'cancelled']);

    // Entrambe le designazioni appartengono alla stessa gara per questo test
    $declined->update(['match_id' => $active->match_id]);

    $match = $active->match;

    $this->actingAs($user)
        ->put(route('rugby-matches.update', $match), [
            'date_time' => $match->date_time->format('Y-m-d\TH:i'),
            'venue_id' => $match->venue_id,
            'competition_type' => $match->competition_type,
            'status' => 'cancelled',
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
        ])
        ->assertRedirect(route('rugby-matches.index'));

    Mail::assertSent(MatchCancelledMail::class, 1);
    Mail::assertSent(MatchCancelledMail::class, fn ($mail) => $mail->designation->id === $active->id
        && $mail->hasTo($active->referee->email)
    );
});

test('deleting a match notifies referees with active designations', function () {
    Mail::fake();

    $user = User::factory()->create();
    $designation = createDesignation($user);
    $match = $designation->match;

    $this->actingAs($user)
        ->delete(route('rugby-matches.destroy', $match))
        ->assertRedirect(route('rugby-matches.index'));

    Mail::assertSent(MatchCancelledMail::class, fn ($mail) => $mail->designation->id === $designation->id
        && $mail->hasTo($designation->referee->email)
    );
});
