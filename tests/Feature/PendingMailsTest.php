<?php

use App\Mail\DesignationRemovedMail;
use App\Models\User;
use App\Support\PendingMails;
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
    $selection = allPendingMailsSelected();

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'), $selection)
        ->assertRedirect(route('designations.index'))
        ->assertSessionHas('success', 'Email inviata.');

    Mail::assertSent(DesignationRemovedMail::class, 1);

    $this->actingAs($user)
        ->get(route('designations.index'))
        ->assertDontSee('Inviare questa email?');

    // Una seconda conferma (es. doppio clic) non reinvia nulla
    $this->actingAs($user)->post(route('pending-mails.send'), $selection);
    Mail::assertSent(DesignationRemovedMail::class, 1);
});

test('with several pending emails the dialog offers a checkbox for each one', function () {
    $user = User::factory()->create();
    $first = createDesignation($user);
    $second = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $first));
    $this->actingAs($user)->delete(route('designations.destroy', $second));

    $response = $this->actingAs($user)->get(route('designations.index'));

    $response->assertOk()->assertSee('Inviare queste 2 email?');

    foreach (PendingMails::all() as $mail) {
        $response->assertSee('type="checkbox" name="selected[]" value="'.$mail['id'].'"', false);
    }
});

test('a single pending email is offered without a checkbox', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('designations.destroy', createDesignation($user)));

    $this->actingAs($user)
        ->get(route('designations.index'))
        ->assertOk()
        ->assertDontSee('type="checkbox" name="selected[]"', false);
});

test('only the selected emails are sent and the unselected ones are discarded', function () {
    $user = User::factory()->create();
    $kept = createDesignation($user);
    $skipped = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $kept));
    $this->actingAs($user)->delete(route('designations.destroy', $skipped));

    $mails = PendingMails::all();

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'), [
            'offered' => array_column($mails, 'id'),
            'selected' => [$mails[0]['id']],
        ])
        ->assertSessionHas('success', 'Email inviata.');

    Mail::assertSent(DesignationRemovedMail::class, 1);
    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->hasTo($kept->referee->email));
    Mail::assertNotSent(DesignationRemovedMail::class, fn ($mail) => $mail->hasTo($skipped->referee->email));
    expect(PendingMails::count())->toBe(0);
});

test('unticking every email sends nothing and closes the dialog', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('designations.destroy', createDesignation($user)));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'), ['offered' => array_column(PendingMails::all(), 'id')])
        ->assertSessionHas('success', 'Nessuna email inviata.');

    Mail::assertNothingSent();
    expect(PendingMails::count())->toBe(0);
});

test('a request without the list of offered emails sends and discards nothing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('designations.destroy', createDesignation($user)));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'))
        ->assertSessionHasErrors('offered');

    Mail::assertNothingSent();
    expect(PendingMails::count())->toBe(1);
});

test('an email queued after the choice stays pending instead of being discarded', function () {
    $user = User::factory()->create();
    $offered = createDesignation($user);
    $queuedLater = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $offered));
    $selection = allPendingMailsSelected();
    $this->actingAs($user)->delete(route('designations.destroy', $queuedLater));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'), $selection);

    Mail::assertSent(DesignationRemovedMail::class, 1);
    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->hasTo($offered->referee->email));
    expect(PendingMails::count())->toBe(1);
});

test('when a send fails the unsent selected emails stay pending and the unselected ones do not come back', function () {
    $user = User::factory()->create();
    $skipped = createDesignation($user);
    $selected = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $skipped));
    $this->actingAs($user)->delete(route('designations.destroy', $selected));

    $mails = PendingMails::all();

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP non raggiungibile'));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.send'), [
            'offered' => array_column($mails, 'id'),
            'selected' => [$mails[1]['id']],
        ])
        ->assertSessionHas('error', 'Invio non riuscito: 1 email restano in attesa. Riprova.');

    expect(array_column(PendingMails::all(), 'id'))->toBe([$mails[1]['id']]);
});

test('discarding drops the pending emails without sending them', function () {
    $user = User::factory()->create();
    $designation = createDesignation($user);

    $this->actingAs($user)->delete(route('designations.destroy', $designation));

    $this->actingAs($user)
        ->from(route('designations.index'))
        ->post(route('pending-mails.discard'))
        ->assertRedirect(route('designations.index'));

    $this->actingAs($user)->post(route('pending-mails.send'), allPendingMailsSelected());

    Mail::assertNothingSent();
    expect($designation->fresh())->toBeNull();
});
