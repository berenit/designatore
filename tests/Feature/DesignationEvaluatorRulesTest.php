<?php

use App\Models\Designation;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->designatore = User::factory()->create();
    $this->designation = createDesignation($this->designatore);
    $this->match = $this->designation->match;
    $this->match->update(['required_roles' => ['Arbitro', 'Valutatore']]);
    $this->arbitro = $this->designation->referee;
});

function refereeWith(string $name, string $level, bool $evaluator): Referee
{
    return Referee::create([
        'name' => $name,
        'email' => str($name)->slug().'@example.com',
        'license_level' => $level,
        'is_evaluator' => $evaluator,
    ]);
}

test('un Fuori Ruolo valutatore può essere designato come Valutatore', function () {
    $valutatore = refereeWith('Fuori Valutatore', Referee::FUORI_RUOLO, true);

    $this->actingAs($this->designatore)
        ->post(route('designations.store'), [
            'match_id' => $this->match->id,
            'referees' => ['Arbitro' => $this->arbitro->id, 'Valutatore' => $valutatore->id],
        ])
        ->assertSessionHasNoErrors();

    expect(Designation::where('role', 'Valutatore')->value('referee_id'))->toBe($valutatore->id);
});

test('un arbitro non valutatore non può essere designato come Valutatore', function () {
    $nonValutatore = refereeWith('Non Valutatore', 'Regionale', false);

    $this->actingAs($this->designatore)
        ->post(route('designations.store'), [
            'match_id' => $this->match->id,
            'referees' => ['Arbitro' => $this->arbitro->id, 'Valutatore' => $nonValutatore->id],
        ])
        ->assertSessionHasErrors('referees');

    expect(Designation::where('role', 'Valutatore')->exists())->toBeFalse();
});

test('un Fuori Ruolo non può svolgere attività in campo', function (string $role) {
    $fuoriRuolo = refereeWith('Fuori Ruolo Valutatore', Referee::FUORI_RUOLO, true);
    $this->match->update(['required_roles' => ['Arbitro', 'Assistente 1', 'Assistente 2', '4° uomo', '5° uomo']]);

    $referees = $role === 'Arbitro'
        ? ['Arbitro' => $fuoriRuolo->id]
        : ['Arbitro' => $this->arbitro->id, $role => $fuoriRuolo->id];

    $this->actingAs($this->designatore)
        ->post(route('designations.store'), ['match_id' => $this->match->id, 'referees' => $referees])
        ->assertSessionHasErrors('referees');

    expect(Designation::where('referee_id', $fuoriRuolo->id)->exists())->toBeFalse();
})->with(['Arbitro', 'Assistente 1', 'Assistente 2', '4° uomo', '5° uomo']);

test('un Fuori Ruolo non può essere Direttore di concentramento', function () {
    $fuoriRuolo = refereeWith('Fuori Direttore', Referee::FUORI_RUOLO, true);

    expect($fuoriRuolo->canBeDesignatedAs('Direttore di concentramento'))->toBeFalse();
});

test('un Fuori Ruolo può essere designato come Tutor, anche se non è abilitato come Valutatore', function () {
    $fuoriRuolo = refereeWith('Fuori Tutor', Referee::FUORI_RUOLO, false);
    $this->match->update(['required_roles' => ['Arbitro', 'Tutor']]);

    $this->actingAs($this->designatore)
        ->post(route('designations.store'), [
            'match_id' => $this->match->id,
            'referees' => ['Arbitro' => $this->arbitro->id, 'Tutor' => $fuoriRuolo->id],
        ])
        ->assertSessionHasNoErrors();

    expect(Designation::where('role', 'Tutor')->value('referee_id'))->toBe($fuoriRuolo->id);
});

test('un Fuori Ruolo indisponibile non può essere designato come Tutor o Valutatore', function (string $role) {
    $fuoriRuolo = refereeWith('Fuori Indisponibile', Referee::FUORI_RUOLO, true);
    $this->match->update(['required_roles' => ['Arbitro', 'Tutor', 'Valutatore']]);
    $date = $this->match->fresh()->date_time;
    $fuoriRuolo->unavailabilities()->create(['start_date' => $date, 'end_date' => $date]);

    $this->actingAs($this->designatore)
        ->post(route('designations.store'), [
            'match_id' => $this->match->id,
            'referees' => ['Arbitro' => $this->arbitro->id, $role => $fuoriRuolo->id],
        ])
        ->assertSessionHasErrors();

    expect(Designation::where('referee_id', $fuoriRuolo->id)->exists())->toBeFalse();
})->with(['Tutor', 'Valutatore']);

test('in modifica non si può passare a Valutatore un arbitro non valutatore', function () {
    $this->actingAs($this->designatore)
        ->put(route('designations.update', $this->designation), [
            'match_id' => $this->match->id,
            'referee_id' => $this->arbitro->id,
            'role' => 'Valutatore',
            'status' => 'pending',
        ])
        ->assertSessionHasErrors('referee_id');

    expect($this->designation->fresh()->role)->toBe('Arbitro');
});

test('una designazione già salvata resta modificabile anche se l\'arbitro è diventato Fuori Ruolo', function () {
    $this->arbitro->update(['license_level' => Referee::FUORI_RUOLO]);

    $this->actingAs($this->designatore)
        ->put(route('designations.update', $this->designation), [
            'match_id' => $this->match->id,
            'referee_id' => $this->arbitro->id,
            'role' => 'Arbitro',
            'status' => 'confirmed',
            'notes' => 'Nota aggiornata',
        ])
        ->assertSessionHasNoErrors();

    expect($this->designation->fresh()->notes)->toBe('Nota aggiornata');
});

test('la scheda arbitro salva la spunta Valutatore e la categoria Fuori Ruolo', function () {
    $payload = [
        'name' => 'Luca Bianchi',
        'email' => 'luca@example.com',
        'license_number' => '555',
        'license_level' => Referee::FUORI_RUOLO,
        'availability_status' => 'available',
        'is_evaluator' => '1',
    ];

    $this->actingAs($this->designatore)->post(route('referees.store'), $payload)->assertSessionHasNoErrors();
    $referee = Referee::where('email', 'luca@example.com')->first();
    expect($referee->is_evaluator)->toBeTrue()->and($referee->isFuoriRuolo())->toBeTrue();

    // Checkbox non spuntata: il campo non arriva e va salvato come false
    unset($payload['is_evaluator']);
    $this->actingAs($this->designatore)->put(route('referees.update', $referee), $payload)->assertSessionHasNoErrors();
    expect($referee->fresh()->is_evaluator)->toBeFalse();
});

test('i form di designazione filtrano gli arbitri per ruolo', function () {
    $fuoriRuolo = refereeWith('Fuori Valutatore', Referee::FUORI_RUOLO, true);

    $create = $this->actingAs($this->designatore)->get(route('designations.create'))->assertOk()->getContent();
    expect($create)->toContain("eligibleFor(role, true, true) || referees[role] == {$fuoriRuolo->id}");

    $edit = $this->actingAs($this->designatore)->get(route('designations.edit', $this->designation))->assertOk()->getContent();
    expect($edit)->toContain("(role === 'Valutatore' ? true : (role === 'Tutor' || false)) || refereeId === '{$fuoriRuolo->id}'");
});
