<?php

use App\Models\Referee;
use App\Models\User;

function refereePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mario Rossi',
        'email' => 'mario.rossi@example.com',
        'license_number' => '123456',
        'phone' => null,
        'license_level' => 'Regionale',
        'availability_status' => 'available',
    ], $overrides);
}

test('un arbitro viene creato con il numero di tessera', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('referees.store'), refereePayload(['license_number' => '0012345678']))
        ->assertRedirect(route('referees.index'));

    // Gli zeri iniziali devono essere preservati.
    expect(Referee::first()->license_number)->toBe('0012345678');
});

test('il numero di tessera è obbligatorio e deve avere da 1 a 10 cifre', function (string $value) {
    $this->actingAs(User::factory()->create())
        ->post(route('referees.store'), refereePayload(['license_number' => $value]))
        ->assertSessionHasErrors('license_number');

    expect(Referee::count())->toBe(0);
})->with(['', '12345678901', '12a45', '-123', '12 34']);

test('il numero di tessera deve essere unico', function () {
    Referee::create(refereePayload(['email' => 'altro@example.com']));

    $this->actingAs(User::factory()->create())
        ->post(route('referees.store'), refereePayload())
        ->assertSessionHasErrors('license_number');
});

test('un arbitro esistente senza tessera può riceverla in modifica mantenendo la propria', function () {
    $user = User::factory()->create();
    $referee = Referee::create(['name' => 'Luigi Bianchi', 'email' => 'luigi@example.com']);

    expect($referee->fresh()->license_number)->toBeNull();

    $this->actingAs($user)
        ->put(route('referees.update', $referee), refereePayload(['email' => 'luigi@example.com', 'license_number' => '1']))
        ->assertSessionHasNoErrors();

    expect($referee->fresh()->license_number)->toBe('1');

    // Riscrivere lo stesso numero non deve violare l'unicità.
    $this->actingAs($user)
        ->put(route('referees.update', $referee), refereePayload(['email' => 'luigi@example.com', 'license_number' => '1']))
        ->assertSessionHasNoErrors();
});
