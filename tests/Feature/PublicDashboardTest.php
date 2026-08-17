<?php

use App\Models\RugbyMatch;
use App\Models\Team;
use App\Models\Venue;

function createScheduledMatch(string $leagueDivision): RugbyMatch
{
    $venue = Venue::create(['name' => 'Stadio Test', 'city' => 'Roma', 'address' => 'Via Test 1']);
    $home = Team::create(['name' => 'Casa '.$leagueDivision, 'city' => 'Roma', 'league_division' => $leagueDivision]);
    $away = Team::create(['name' => 'Ospiti '.$leagueDivision, 'city' => 'Milano', 'league_division' => $leagueDivision]);

    return RugbyMatch::create([
        'date_time' => now()->addWeek(),
        'venue_id' => $venue->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
    ]);
}

test('public dashboard lists upcoming matches with their category', function () {
    createScheduledMatch('Serie A');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Serie A');
});

test('public dashboard can be filtered by category', function () {
    $serieA = createScheduledMatch('Serie A');
    $u18 = createScheduledMatch('U18');

    $response = $this->get(route('home', ['category' => 'U18']));

    $response->assertOk()
        ->assertSee($u18->label)
        ->assertDontSee($serieA->label);
});
