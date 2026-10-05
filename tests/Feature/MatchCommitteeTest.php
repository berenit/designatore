<?php

use App\Models\RugbyMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Mail;

function committeeMatchPayload(array $overrides = []): array
{
    $venue = Venue::create(['name' => 'Stadio Test', 'city' => 'Roma', 'address' => 'Via Test 1']);
    $home = Team::create(['name' => 'Casa', 'city' => 'Roma', 'league_division' => 'Serie A']);
    $away = Team::create(['name' => 'Ospiti', 'city' => 'Milano', 'league_division' => 'Serie A']);

    return array_merge([
        'date_time' => now()->addWeek()->format('Y-m-d\TH:i'),
        'venue_id' => $venue->id,
        'competition_type' => 'Campionato',
        'status' => 'scheduled',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ], $overrides);
}

beforeEach(fn () => Mail::fake());

test('a new match stores the selected committee', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('rugby-matches.store'), committeeMatchPayload(['committee' => 'Lazio']))
        ->assertRedirect(route('rugby-matches.index'));

    expect(RugbyMatch::first()->committee)->toBe('Lazio');
});

test('a new match defaults to Abruzzo when no committee is sent', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('rugby-matches.store'), committeeMatchPayload())
        ->assertRedirect(route('rugby-matches.index'));

    expect(RugbyMatch::first()->committee)->toBe('Abruzzo');
});

test('an unknown committee is rejected', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('rugby-matches.store'), committeeMatchPayload(['committee' => 'Toscana']))
        ->assertSessionHasErrors('committee');

    expect(RugbyMatch::count())->toBe(0);
});

test('updating a match without committee keeps the current one', function () {
    $user = User::factory()->create();
    $payload = committeeMatchPayload(['committee' => 'Lazio']);

    $this->actingAs($user)->post(route('rugby-matches.store'), $payload);
    $match = RugbyMatch::first();

    unset($payload['committee']);
    $this->actingAs($user)
        ->put(route('rugby-matches.update', $match), array_merge($payload, ['status' => 'postponed']))
        ->assertRedirect(route('rugby-matches.index'));

    expect($match->fresh()->committee)->toBe('Lazio');
});

test('the create form shows the committee select with Abruzzo preselected', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('rugby-matches.create'))
        ->assertOk()
        ->assertSee('name="committee"', false)
        ->assertSee('<option value="Abruzzo" selected', false)
        ->assertSee('Lazio');
});
