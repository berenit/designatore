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

test('reports can be filtered by one or more committees', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore)->match->update(['committee' => 'Lazio']);
    createDesignation($designatore)->match->update(['committee' => 'Abruzzo']);

    $this->actingAs($designatore)
        ->get(route('reports.text', ['committees' => ['Lazio']]))
        ->assertOk()
        ->assertSee('Comitato Lazio')
        ->assertDontSee('Comitato Abruzzo');

    $this->actingAs($designatore)
        ->get(route('reports.text', ['committees' => ['Lazio', 'Abruzzo']]))
        ->assertOk()
        ->assertSee('Comitato Lazio')
        ->assertSee('Comitato Abruzzo');
});

test('reports can be filtered by one or more categories', function () {
    $designatore = User::factory()->create();
    $serieA = createDesignation($designatore);
    $u18 = createDesignation($designatore);
    $u18->match->homeTeam->update(['league_division' => 'Under 18']);
    $serieC = createDesignation($designatore);
    $serieC->match->homeTeam->update(['league_division' => 'Serie C']);

    $this->actingAs($designatore)
        ->get(route('reports.text', ['categories' => ['Under 18', 'Serie C']]))
        ->assertOk()
        ->assertSee('🏆 Under 18', false)
        ->assertSee('🏆 Serie C', false)
        ->assertDontSee('🏆 Serie A', false);
});

test('reports include all committees and categories when no filter is selected', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore)->match->update(['committee' => 'Lazio']);
    createDesignation($designatore)->match->homeTeam->update(['league_division' => 'Under 18']);

    $this->actingAs($designatore)
        ->get(route('reports.text'))
        ->assertOk()
        ->assertSee('Comitato Lazio')
        ->assertSee('Comitato Abruzzo')
        ->assertSee('🏆 Under 18', false)
        ->assertSee('🏆 Serie A', false);
});

test('only the markdown and pdf reports show the referee license number next to the name', function () {
    $designatore = User::factory()->create();
    $designation = createDesignation($designatore);
    $designation->referee->update(['license_number' => '111111']);

    $markdown = $this->actingAs($designatore)->get(route('reports.markdown'));
    $markdown->assertOk();
    expect($markdown->getContent())->toContain('**Arbitro**: **Mario Rossi** (Tess. 111111) |')->not->toContain('In attesa)');

    // Il PDF usa la stessa etichetta della tessera, resa dal modello
    expect($designation->referee->fresh()->license_label)->toBe('(Tess. 111111)');

    $this->actingAs($designatore)
        ->get(route('reports.text'))
        ->assertOk()
        ->assertSee('Mario Rossi — Arbitro', false)
        ->assertDontSee('Tess.', false);

    $this->actingAs($designatore)
        ->get(route('reports.index', ['date_from' => now()->toDateString(), 'date_to' => now()->addMonth()->toDateString()]))
        ->assertOk()
        ->assertSee('Mario Rossi')
        ->assertDontSee('Tess.');
});

test('the markdown report shows only the referee name when the license number is missing', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore);

    $markdown = $this->actingAs($designatore)->get(route('reports.markdown'));
    $markdown->assertOk();
    expect($markdown->getContent())->toContain('**Arbitro**: **Mario Rossi** |')->not->toContain('Tess.');
});

test('the markdown report shows the match category in bold in its own column', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore);

    $markdown = $this->actingAs($designatore)->get(route('reports.markdown'));
    $markdown->assertOk();
    expect($markdown->getContent())
        ->toContain('| Data | Incontro | Categoria | Comitato | Campo | Arbitri & ruoli |')
        ->toContain('| **Serie A** | Abruzzo |');
});

test('the markdown report has no summary section', function () {
    $designatore = User::factory()->create();
    createDesignation($designatore);

    $markdown = $this->actingAs($designatore)->get(route('reports.markdown'));
    $markdown->assertOk();
    expect($markdown->getContent())->not->toContain('Riepilogo');
});
