<?php

use App\Models\RugbyMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;

function dashboardMatch(Carbon $dateTime): RugbyMatch
{
    $venue = Venue::firstOrCreate(['name' => 'Stadio Test'], ['city' => 'Roma', 'address' => 'Via Test 1']);

    return RugbyMatch::create([
        'date_time' => $dateTime,
        'venue_id' => $venue->id,
        'home_team_id' => Team::create(['name' => 'Casa '.uniqid(), 'city' => 'Roma', 'league_division' => 'Serie A'])->id,
        'away_team_id' => Team::create(['name' => 'Ospiti '.uniqid(), 'city' => 'Roma', 'league_division' => 'Serie A'])->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
    ]);
}

test('the dashboard counts only this week\'s upcoming matches and those without designation', function () {
    // Mercoledì: nella stessa settimana ci sono sia partite già giocate sia partite future
    $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));

    dashboardMatch(now()->addDays(3)->setTime(15, 0));          // domenica di questa settimana: conta
    dashboardMatch(now()->subDay()->setTime(15, 0));            // già giocata: non conta
    dashboardMatch(now()->addWeek()->setTime(15, 0));           // settimana prossima: non conta
    createDesignation(User::factory()->create());               // designata (settimana prossima): non conta

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn ($stats) => $stats['matches_without_designation'] === 1 && $stats['upcoming_matches'] === 1)
        ->assertSee('Partite della settimana senza designazione')
        ->assertSee('Partite in programma questa settimana');
});

test('the dashboard counts pending and confirmed designations only for this week\'s matches', function () {
    // Mercoledì: le designazioni della settimana contano anche se la partita è già stata giocata
    $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));

    $user = User::factory()->create();
    $thisWeek = fn (array $attributes) => tap(createDesignation($user, $attributes), fn ($d) => $d->match->update(['date_time' => now()->addDays(3)->setTime(15, 0)]));

    $thisWeek(['status' => 'pending']);
    $thisWeek(['status' => 'confirmed']);
    tap(createDesignation($user, ['status' => 'confirmed']), fn ($d) => $d->match->update(['date_time' => now()->subDay()->setTime(15, 0)]));
    createDesignation($user, ['status' => 'pending']);    // settimana prossima: non conta
    createDesignation($user, ['status' => 'confirmed']);  // settimana prossima: non conta

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn ($stats) => $stats['pending_designations'] === 1 && $stats['confirmed_designations'] === 2)
        ->assertSee('Designazioni in attesa questa settimana')
        ->assertSee('Designazioni accettate questa settimana');
});

test('the latest designations box lists only designations for this week\'s matches', function () {
    $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));

    $user = User::factory()->create();
    $thisWeek = createDesignation($user);
    $thisWeek->match->update(['date_time' => now()->addDays(3)->setTime(15, 0)]);
    $nextWeek = createDesignation($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('recentDesignations', fn ($designations) => $designations->pluck('id')->all() === [$thisWeek->id])
        ->assertSee('Ultime designazioni della settimana')
        // Categoria della partita sia in Prossime partite sia in Ultime designazioni
        ->assertSeeInOrder(['Prossime partite', ' · Serie A', 'Ultime designazioni della settimana', ' · Serie A']);
});
