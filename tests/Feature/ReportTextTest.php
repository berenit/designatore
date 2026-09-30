<?php

use App\Models\User;

test('text report includes the match category for each designation', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore);

    $this->actingAs($designatore)
        ->get(route('reports.text'))
        ->assertOk()
        ->assertSee('🏆 Serie A', false);
});
