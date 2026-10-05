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

test('reports include the match committee', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore)->match->update(['committee' => 'Lazio']);

    $this->actingAs($designatore)
        ->get(route('reports.text'))
        ->assertOk()
        ->assertSee('🗺 Comitato Lazio', false);

    $markdown = $this->actingAs($designatore)->get(route('reports.markdown'));
    $markdown->assertOk();
    expect($markdown->getContent())->toContain('| Lazio |');

    $this->actingAs($designatore)
        ->get(route('reports.index', ['date_from' => now()->toDateString(), 'date_to' => now()->addMonth()->toDateString()]))
        ->assertOk()
        ->assertSee('Comitato Lazio');

    $this->actingAs($designatore)
        ->get(route('reports.pdf'))
        ->assertOk();
});
