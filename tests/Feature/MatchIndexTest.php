<?php

use App\Models\RugbyMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;

function indexMatch(string $home, string $away, string $category, Carbon $dateTime): RugbyMatch
{
    $venue = Venue::firstOrCreate(['name' => 'Stadio Test'], ['city' => 'Roma', 'address' => 'Via Test 1']);

    return RugbyMatch::create([
        'date_time' => $dateTime,
        'venue_id' => $venue->id,
        'home_team_id' => Team::create(['name' => $home, 'city' => 'Roma', 'league_division' => $category])->id,
        'away_team_id' => Team::create(['name' => $away, 'city' => 'Roma', 'league_division' => $category])->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
    ]);
}

test('matches index shows only the selected week', function () {
    indexMatch('Casa Corrente', 'Ospiti Corrente', 'Serie A', now()->startOfWeek()->addDays(2)->setTime(15, 0));
    indexMatch('Casa Prossima', 'Ospiti Prossima', 'Serie A', now()->startOfWeek()->addWeek()->addDays(2)->setTime(15, 0));

    $this->actingAs(User::factory()->create())
        ->get(route('rugby-matches.index'))
        ->assertOk()
        ->assertSee('Casa Corrente')
        ->assertDontSee('Casa Prossima');

    $this->actingAs(User::factory()->create())
        ->get(route('rugby-matches.index', ['week' => now()->addWeek()->toDateString()]))
        ->assertOk()
        ->assertSee('Casa Prossima')
        ->assertDontSee('Casa Corrente');
});

test('matches index shows the category column and filters by category', function () {
    $date = now()->startOfWeek()->addDays(2)->setTime(15, 0);
    indexMatch('Casa Senior', 'Ospiti Senior', 'Serie A', $date);
    indexMatch('Casa Giovani', 'Ospiti Giovani', 'U18', $date);

    $this->actingAs(User::factory()->create())
        ->get(route('rugby-matches.index'))
        ->assertOk()
        ->assertSeeInOrder(['Categoria', 'Serie A'])
        ->assertSee('Casa Giovani');

    $this->actingAs(User::factory()->create())
        ->get(route('rugby-matches.index', ['category' => 'U18']))
        ->assertOk()
        ->assertSee('Casa Giovani')
        ->assertDontSee('Casa Senior');
});

test('designations index filters by category', function () {
    $date = now()->startOfWeek()->addDays(2)->setTime(15, 0);
    indexMatch('Casa Senior', 'Ospiti Senior', 'Serie A', $date);
    indexMatch('Casa Giovani', 'Ospiti Giovani', 'U18', $date);

    $this->actingAs(User::factory()->create())
        ->get(route('designations.index', ['category' => 'Serie A']))
        ->assertOk()
        ->assertSee('Casa Senior')
        ->assertDontSee('Casa Giovani');
});
