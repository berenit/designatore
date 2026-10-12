<?php

use App\Mail\DesignationRemovedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

test('a change that triggers emails asks for confirmation instead of sending them', function () {
    $user = User::factory()->create();
    $designation = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $designation));

    Mail::assertNothingSent();

    $this->actingAs($user)
        ->get(route('designations.index'))
        ->assertOk()
        ->assertSee('Inviare questa email?')
        ->assertSee('Designazione rimossa')
        ->assertSee($designation->referee->email)
        ->assertSee('Invia email')
        ->assertSee('Non inviare');
});

test('confirming sends the pending emails once and closes the dialog', function () {
    $user = User::factory()->create();
    $designation = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $designation));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'))
        ->assertRedirect(route('designations.index'))
        ->assertSessionHas('success', 'Email inviata.');

    Mail::assertSent(DesignationRemovedMail::class, 1);

    $this->actingAs($user)
        ->get(route('designations.index'))
        ->assertDontSee('Inviare questa email?');

    // Una seconda conferma non reinvia nulla
    $this->actingAs($user)->post(route('pending-mails.send'));
    Mail::assertSent(DesignationRemovedMail::class, 1);
});

test('discarding drops the pending emails without sending them', function () {
    $user = User::factory()->create();
    $designation = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $designation));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.discard'))
        ->assertRedirect(route('designations.index'));

    $this->actingAs($user)->post(route('pending-mails.send'));

    Mail::assertNothingSent();
    expect($designation->fresh())->toBeNull();
});
