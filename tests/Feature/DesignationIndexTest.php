<?php

use App\Models\User;

test('designations index shows the match category in its own column', function () {
    $designatore = User::factory()->create();
    $designation = createDesignation($designatore);
    $designation->match->homeTeam->update(['league_division' => 'Under 18']);

    $this->actingAs($designatore)
        ->get(route('designations.index', ['week' => $designation->match->date_time->toDateString()]))
        ->assertOk()
        ->assertSeeInOrder(['Categoria', 'Under 18']);
});
