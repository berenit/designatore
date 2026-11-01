<?php

use App\Mail\DesignationNotificationMail;
use App\Mail\DesignationRemovedMail;
use App\Mail\MatchUpdatedMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(fn () => Mail::fake());

/** Gara con Arbitro e Valutatore designati. Ritorna [arbitro, valutatore]. */
function matchWithEvaluator(User $user, array $evaluatorAttributes = []): array
{
    $designation = createDesignation($user);
    $designation->match->update(['required_roles' => ['Arbitro', 'Valutatore']]);

    $referee = Referee::create([
        'name' => 'Valutatore Test',
        'email' => 'valutatore-'.Str::random(10).'@example.com',
        'is_evaluator' => true,
    ]);

    $evaluator = Designation::create([
        'match_id' => $designation->match_id,
        'referee_id' => $referee->id,
        'role' => 'Valutatore',
        'assigned_by' => $user->id,
        'assignment_date' => now(),
        'status' => 'pending',
    ]);
    $evaluator->forceFill($evaluatorAttributes)->save();

    return [$designation->fresh(), $evaluator->fresh()];
}

function matchPayload($match, array $extra = []): array
{
    return [
        'date_time' => $match->date_time->format('Y-m-d\TH:i'),
        'venue_id' => $match->venue_id,
        'competition_type' => $match->competition_type,
        'status' => $match->status,
        'home_team_id' => $match->home_team_id,
        'away_team_id' => $match->away_team_id,
        ...$extra,
    ];
}

test('removing a role from a match deletes its designation and warns the referee instead of asking to confirm', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user);
    $confirmUrl = $evaluator->responseUrl('confirm');

    $this->actingAs($user)
        ->put(route('rugby-matches.update', $arbitro->match), matchPayload($arbitro->match))
        ->assertRedirect();

    sendPendingMails($this);

    expect(Designation::find($evaluator->id))->toBeNull()
        ->and(Designation::find($arbitro->id))->not->toBeNull();

    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->hasTo($evaluator->referee->email));
    Mail::assertNotSent(MatchUpdatedMail::class, fn ($mail) => $mail->hasTo($evaluator->referee->email));

    // Il link ricevuto prima della rimozione non conferma più nulla
    $this->get($confirmUrl)->assertStatus(410)->assertSee('Designazione non più valida');
    expect(Designation::find($evaluator->id))->toBeNull();
});

test('a role still required keeps its designation when the match is edited', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user);

    $this->actingAs($user)
        ->put(route('rugby-matches.update', $arbitro->match), matchPayload($arbitro->match, ['extra_roles' => ['evaluator']]))
        ->assertRedirect();

    expect(Designation::find($evaluator->id))->not->toBeNull();
});

test('a designation on a role the match never required is not removed by editing the match', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user);
    $arbitro->match->update(['required_roles' => null]);

    $this->actingAs($user)
        ->put(route('rugby-matches.update', $arbitro->match), matchPayload($arbitro->match))
        ->assertRedirect();

    expect(Designation::find($evaluator->id))->not->toBeNull();
});

test('removing a role keeps completed designations as history', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user, ['status' => 'completed']);

    $this->actingAs($user)
        ->put(route('rugby-matches.update', $arbitro->match), matchPayload($arbitro->match));

    expect(Designation::find($evaluator->id))->not->toBeNull();
    Mail::assertNotSent(DesignationRemovedMail::class);
});

test('removing a role does not warn a referee who had already declined', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user, ['status' => 'cancelled']);

    $this->actingAs($user)
        ->put(route('rugby-matches.update', $arbitro->match), matchPayload($arbitro->match));

    sendPendingMails($this);

    expect(Designation::find($evaluator->id))->toBeNull();
    Mail::assertNotSent(DesignationRemovedMail::class);
});

test('setting a role to nobody in the designation form removes the designation and warns the referee', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user);
    $confirmUrl = $evaluator->responseUrl('confirm');

    $this->actingAs($user)
        ->post(route('designations.store'), [
            'match_id' => $arbitro->match_id,
            'referees' => ['Arbitro' => $arbitro->referee_id, 'Valutatore' => ''],
        ])
        ->assertRedirect(route('designations.index'));

    sendPendingMails($this);

    expect(Designation::find($evaluator->id))->toBeNull();
    Mail::assertSent(DesignationRemovedMail::class, 1);
    Mail::assertSent(DesignationRemovedMail::class, fn ($mail) => $mail->hasTo($evaluator->referee->email));

    $this->get($confirmUrl)->assertStatus(410);
});

test('a role not submitted by the designation form stays assigned', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user);

    $this->actingAs($user)
        ->post(route('designations.store'), [
            'match_id' => $arbitro->match_id,
            'referees' => ['Arbitro' => $arbitro->referee_id],
        ])
        ->assertRedirect(route('designations.index'));

    expect(Designation::find($evaluator->id))->not->toBeNull();
    Mail::assertNothingSent();
});

test('a referee can leave a role and take another one in the same submission', function () {
    $user = User::factory()->create();
    [$arbitro, $evaluator] = matchWithEvaluator($user);
    $arbitro->match->update(['required_roles' => ['Arbitro', 'Tutor', 'Valutatore']]);

    $this->actingAs($user)
        ->post(route('designations.store'), [
            'match_id' => $arbitro->match_id,
            'referees' => ['Arbitro' => $arbitro->referee_id, 'Valutatore' => '', 'Tutor' => $evaluator->referee_id],
        ])
        ->assertRedirect(route('designations.index'))
        ->assertSessionHasNoErrors();

    expect(Designation::find($evaluator->id))->toBeNull()
        ->and(Designation::where(['match_id' => $arbitro->match_id, 'role' => 'Tutor'])->value('referee_id'))->toBe($evaluator->referee_id);
});

test('the link of a replaced referee no longer works while the new referee can answer', function () {
    $user = User::factory()->create();
    $designation = createDesignation($user);
    $oldLink = $designation->responseUrl('confirm');
    $newReferee = Referee::create(['name' => 'Nuovo Arbitro', 'email' => 'nuovo-link@example.com']);

    $this->actingAs($user)->put(route('designations.update', $designation), [
        'match_id' => $designation->match_id,
        'referee_id' => $newReferee->id,
        'role' => $designation->role,
        'status' => 'pending',
    ]);

    $this->get($oldLink)->assertStatus(410)->assertSee('Designazione non più valida');
    expect($designation->fresh()->status)->toBe('pending');

    sendPendingMails($this);

    $newLink = null;
    Mail::assertSent(DesignationNotificationMail::class, function ($mail) use (&$newLink) {
        $newLink = $mail->acceptUrl;

        return true;
    });

    $this->get($newLink)->assertOk();
    expect($designation->fresh()->status)->toBe('confirmed');
});

test('links sent before they carried the recipient keep working', function () {
    $designation = createDesignation(User::factory()->create());

    $legacyLink = URL::signedRoute('designations.respond', ['designation' => $designation->id, 'action' => 'confirm']);

    $this->get($legacyLink)->assertOk();
    expect($designation->fresh()->status)->toBe('confirmed');
});

test('a tampered link to a missing designation is still refused', function () {
    $this->get('/designations/999999/respond/confirm')->assertForbidden();
});
