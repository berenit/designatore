<?php

use App\Models\Referee;
use App\Models\RefereeUnavailability;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

function makeReferee(): Referee
{
    return Referee::create([
        'name' => 'Mario Rossi',
        'email' => 'mario@example.com',
        'license_number' => '123456',
        'license_level' => 'Regionale',
        'availability_status' => 'available',
    ]);
}

test('model:prune elimina solo le indisponibilità già concluse', function () {
    $this->travelTo('2026-10-21 10:00:00');
    $referee = makeReferee();

    $past = $referee->unavailabilities()->create(['start_date' => '2026-10-01', 'end_date' => '2026-10-20']);
    $endsToday = $referee->unavailabilities()->create(['start_date' => '2026-10-15', 'end_date' => '2026-10-21']);
    $future = $referee->unavailabilities()->create(['start_date' => '2026-11-01', 'end_date' => '2026-11-03']);

    Artisan::call('model:prune', ['--model' => [RefereeUnavailability::class]]);

    expect(RefereeUnavailability::pluck('id')->all())
        ->not->toContain($past->id)
        ->toContain($endsToday->id, $future->id);
});

test('la modifica arbitro non mostra le indisponibilità passate', function () {
    $this->travelTo('2026-10-21 10:00:00');
    $referee = makeReferee();
    $referee->unavailabilities()->create(['start_date' => '2026-09-01', 'end_date' => '2026-09-05']);
    $referee->unavailabilities()->create(['start_date' => '2026-11-01', 'end_date' => '2026-11-03']);

    $this->actingAs(User::factory()->create())
        ->get(route('referees.edit', $referee))
        ->assertOk()
        ->assertSee('01/11/2026')
        ->assertDontSee('01/09/2026');
});
