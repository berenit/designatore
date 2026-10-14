<?php

use App\Models\Referee;
use App\Models\User;

test('nella modifica designazione i Derogati disponibili sono in arancione, quelli indisponibili restano grigi', function () {
    $designatore = User::factory()->create();
    $designation = createDesignation($designatore);
    $matchDate = $designation->match->date_time->toDateString();

    $available = Referee::create(['name' => 'Derogato Libero', 'email' => 'libero@example.com', 'license_level' => 'Derogato']);
    $unavailable = Referee::create(['name' => 'Derogato Assente', 'email' => 'assente@example.com', 'license_level' => 'Derogato']);
    $unavailable->unavailabilities()->create(['start_date' => $matchDate, 'end_date' => $matchDate]);

    $html = $this->actingAs($designatore)
        ->get(route('designations.edit', $designation))
        ->assertOk()
        ->getContent();

    $optionFor = fn (Referee $referee) => preg_match('/<option value="'.$referee->id.'"[^>]*>/', $html, $m) ? $m[0] : '';

    expect($optionFor($available))->toContain('text-orange-600')
        ->and($optionFor($unavailable))->toContain('text-gray-400')->not->toContain('text-orange-600')
        ->and($optionFor($designation->referee))->not->toContain('text-orange-600');
});
