<?php

use App\Mail\DesignationDeclinedMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

function createDesignation(User $designatore): Designation
{
    $venue = Venue::create(['name' => 'Stadio Test', 'city' => 'Roma', 'address' => 'Via Test 1']);
    $home = Team::create(['name' => 'Casa', 'city' => 'Roma', 'league_division' => 'Serie A']);
    $away = Team::create(['name' => 'Ospiti', 'city' => 'Milano', 'league_division' => 'Serie A']);

    $match = RugbyMatch::create([
        'date_time' => now()->addWeek(),
        'venue_id' => $venue->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
    ]);

    $referee = Referee::create([
        'name' => 'Mario Rossi',
        'email' => 'mario.rossi@example.com',
    ]);

    return Designation::create([
        'match_id' => $match->id,
        'referee_id' => $referee->id,
        'role' => 'Arbitro',
        'assigned_by' => $designatore->id,
        'assignment_date' => now(),
        'status' => 'pending',
    ]);
}

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
