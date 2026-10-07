<?php

use App\Models\Team;
use App\Models\User;

test('teams sorted by category follow the configured category order', function () {
    foreach (['U14', 'Serie C', 'U18', 'Promozione', 'Serie A', 'U18 Elite', 'U16', 'Serie B'] as $division) {
        Team::create(['name' => 'Squadra '.$division, 'city' => 'Roma', 'league_division' => $division]);
    }

    $expected = ['Serie A', 'Serie B', 'Serie C', 'U18 Elite', 'U18', 'U16', 'U14', 'Promozione'];
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('teams.index', ['sort' => 'league_division', 'dir' => 'asc']))
        ->assertOk()
        ->assertViewHas('teams', fn ($teams) => $teams->pluck('league_division')->all() === $expected);

    $this->actingAs($user)
        ->get(route('teams.index', ['sort' => 'league_division', 'dir' => 'desc']))
        ->assertOk()
        ->assertViewHas('teams', fn ($teams) => $teams->pluck('league_division')->all() === array_reverse($expected));
});

test('teams sorted by category ignore case and extra spaces in the stored value', function () {
    foreach (['U14', 'serie b ', 'Serie A'] as $division) {
        Team::create(['name' => 'Squadra '.$division, 'city' => 'Roma', 'league_division' => $division]);
    }

    $this->actingAs(User::factory()->create())
        ->get(route('teams.index', ['sort' => 'league_division']))
        ->assertOk()
        ->assertViewHas('teams', fn ($teams) => $teams->pluck('league_division')->all() === ['Serie A', 'serie b ', 'U14']);
});
