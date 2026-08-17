<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createDesignation(\App\Models\User $designatore, array $attributes = []): \App\Models\Designation
{
    $venue = \App\Models\Venue::create(['name' => 'Stadio Test', 'city' => 'Roma', 'address' => 'Via Test 1']);
    $home = \App\Models\Team::create(['name' => 'Casa', 'city' => 'Roma', 'league_division' => 'Serie A']);
    $away = \App\Models\Team::create(['name' => 'Ospiti', 'city' => 'Milano', 'league_division' => 'Serie A']);

    $match = \App\Models\RugbyMatch::create([
        'date_time' => now()->addWeek(),
        'venue_id' => $venue->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
    ]);

    $referee = \App\Models\Referee::create([
        'name' => 'Mario Rossi',
        'email' => 'referee-'.\Illuminate\Support\Str::random(10).'@example.com',
    ]);

    $designation = \App\Models\Designation::create([
        'match_id' => $match->id,
        'referee_id' => $referee->id,
        'role' => 'Arbitro',
        'assigned_by' => $designatore->id,
        'assignment_date' => now(),
        'status' => 'pending',
    ]);

    if ($attributes !== []) {
        // forceFill per poter impostare anche attributi non fillable dai
        // test (es. created_at, per simulare designazioni "vecchie").
        $designation->forceFill($attributes)->save();
    }

    return $designation->fresh();
}
