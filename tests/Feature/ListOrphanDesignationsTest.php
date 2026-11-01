<?php

use App\Models\Designation;
use App\Models\User;

test('it reports when there are no orphan designations', function () {
    createDesignation(User::factory()->create());

    $this->artisan('designations:list-orphans')
        ->expectsOutput('Nessuna designazione orfana.')
        ->assertSuccessful();
});

test('it lists designations on a role the match no longer requires without changing them', function () {
    $orphan = createDesignation(User::factory()->create(), ['role' => 'Valutatore', 'status' => 'pending']);

    $this->artisan('designations:list-orphans')
        ->expectsOutputToContain('Designazioni orfane: 1.')
        ->assertSuccessful();

    expect(Designation::find($orphan->id)->status)->toBe('pending');
});

test('it ignores completed designations and roles still required', function () {
    $user = User::factory()->create();
    createDesignation($user, ['role' => 'Valutatore', 'status' => 'completed']);

    $required = createDesignation($user, ['role' => 'Valutatore']);
    $required->match->update(['required_roles' => ['Arbitro', 'Valutatore']]);

    $this->artisan('designations:list-orphans')
        ->expectsOutput('Nessuna designazione orfana.')
        ->assertSuccessful();
});
