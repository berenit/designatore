<?php

use App\Models\RugbyMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;

function createScheduledMatch(string $leagueDivision, ?Carbon $dateTime = null): RugbyMatch
{
    $venue = Venue::create(['name' => 'Stadio Test', 'city' => 'Roma', 'address' => 'Via Test 1']);
    $home = Team::create(['name' => 'Casa '.$leagueDivision, 'city' => 'Roma', 'league_division' => $leagueDivision]);
    $away = Team::create(['name' => 'Ospiti '.$leagueDivision, 'city' => 'Milano', 'league_division' => $leagueDivision]);

    return RugbyMatch::create([
        'date_time' => $dateTime ?? now()->endOfWeek()->setTime(15, 0),
        'venue_id' => $venue->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
    ]);
}

beforeEach(function () {
    // Mercoledì 7 ottobre 2026: la settimana va da lunedì 5 a domenica 11
    $this->travelTo(Carbon::parse('2026-10-07 10:00:00'));
});

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

test('public dashboard shows only matches of the current week', function () {
    $thisWeek = createScheduledMatch('Serie A', Carbon::parse('2026-10-11 15:00:00'));
    $earlierThisWeek = createScheduledMatch('Serie B', Carbon::parse('2026-10-05 20:00:00'));
    $lastWeek = createScheduledMatch('U18', Carbon::parse('2026-10-04 15:00:00'));
    $nextWeek = createScheduledMatch('U16', Carbon::parse('2026-10-12 15:00:00'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($thisWeek->label)
        ->assertSee($earlierThisWeek->label)
        ->assertDontSee($lastWeek->label)
        ->assertDontSee($nextWeek->label);
});

test('guests do not see the referee name before thursday', function () {
    createDesignation(User::factory()->create(), ['status' => 'confirmed'])
        ->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Mario Rossi')
        ->assertSee('Arbitro visibile da giovedì');
});

test('guests see the referee name from thursday', function () {
    createDesignation(User::factory()->create(), ['status' => 'confirmed'])
        ->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);

    $this->travelTo(Carbon::parse('2026-10-08 00:00:00'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mario Rossi');
});

test('authenticated users always see the referee name', function () {
    $user = User::factory()->create();
    createDesignation($user, ['status' => 'confirmed'])
        ->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Mario Rossi');
});

test('public dashboard category filter follows the configured order', function () {
    foreach (['U14', 'Serie C', 'U18', 'Promozione', 'Serie A', 'U18 Elite', 'U16', 'Serie B'] as $division) {
        createScheduledMatch($division);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('categories', fn ($categories) => $categories->all() === [
            'Serie A', 'Serie B', 'Serie C', 'U18 Elite', 'U18', 'U16', 'U14', 'Promozione',
        ]);
});

test('category order ignores case and extra spaces in the stored value', function () {
    foreach (['U14', 'serie b ', 'Serie A', 'U18'] as $division) {
        createScheduledMatch($division);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('categories', fn ($categories) => $categories->all() === ['Serie A', 'serie b ', 'U18', 'U14']);
});
