<?php

use App\Mail\EmailChangeConfirmationMail;
use App\Mail\RefereeUnavailabilityConflictMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\RefereeUnavailability;
use App\Models\RugbyMatch;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function createReferee(array $attributes = []): Referee
{
    return Referee::create([
        'name' => 'Luca Bianchi',
        'email' => 'luca@example.com',
        'license_number' => '123456',
        ...$attributes,
    ]);
}

/** Arbitro che ha già sostituito il numero di tessera con una password personale. */
function activeRefereeUser(array $attributes = []): User
{
    $user = createReferee($attributes)->user;
    $user->update(['password' => 'password-personale', 'must_change_password' => false]);

    return $user->fresh();
}

function confirmationUrl(User $user, string $email): string
{
    return URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), ['user' => $user->id, 'email' => $email]);
}

describe('account', function () {
    test('a referee with a license number gets an account whose first password is the license number', function () {
        $user = createReferee()->user;

        expect($user->role)->toBe(User::ROLE_REFEREE)
            ->and($user->email)->toBe('luca@example.com')
            ->and($user->must_change_password)->toBeTrue()
            ->and(Hash::check('123456', $user->password))->toBeTrue();
    });

    test('a referee without license number gets the account once it is added', function () {
        $referee = createReferee(['license_number' => null]);

        expect($referee->user)->toBeNull();

        $referee->update(['license_number' => '777']);

        expect(Hash::check('777', $referee->fresh()->user->password))->toBeTrue();
    });

    test('a user with the same email is linked and keeps role and password', function () {
        $designatore = User::factory()->create(['email' => 'luca@example.com', 'role' => 'designatore']);

        $referee = createReferee();

        expect($referee->user->id)->toBe($designatore->id)
            ->and($referee->user->role)->toBe('designatore')
            ->and($referee->user->must_change_password)->toBeFalse()
            ->and(Hash::check('password', $referee->user->password))->toBeTrue();
    });

    test('changing the email from the referee form changes the login', function () {
        $referee = createReferee();
        $user = $referee->user;
        $user->update(['pending_email' => 'old-pending@example.com']);

        $referee->update(['email' => 'nuova@example.com', 'name' => 'Luca B.']);

        expect($user->fresh())
            ->email->toBe('nuova@example.com')
            ->name->toBe('Luca B.')
            ->pending_email->toBeNull();
    });

    test('correcting the license number updates the password only before the first access', function () {
        $referee = createReferee();

        $referee->update(['license_number' => '999']);
        expect(Hash::check('999', $referee->user->fresh()->password))->toBeTrue();

        $referee->user->fresh()->update(['password' => 'password-personale', 'must_change_password' => false]);
        $referee->update(['license_number' => '888']);
        expect(Hash::check('password-personale', $referee->user->fresh()->password))->toBeTrue();
    });

    test('the referee form rejects an email used by another account', function () {
        User::factory()->create(['email' => 'designatore@example.com']);
        $referee = createReferee();

        $this->actingAs(User::factory()->create())
            ->from(route('referees.edit', $referee))
            ->put(route('referees.update', $referee), [
                'name' => $referee->name,
                'email' => 'designatore@example.com',
                'license_number' => '123456',
                'license_level' => 'Regionale',
                'availability_status' => 'available',
            ])
            ->assertSessionHasErrors('email');

        expect($referee->fresh()->email)->toBe('luca@example.com');
    });

    test('deleting a referee removes the referee account but not a linked designatore', function () {
        $referee = createReferee();
        $userId = $referee->user->id;
        $referee->delete();
        expect(User::find($userId))->toBeNull();

        $designatore = User::factory()->create(['email' => 'altro@example.com', 'role' => 'designatore']);
        $linked = createReferee(['email' => 'altro@example.com', 'license_number' => '555']);
        $linked->delete();
        expect($designatore->fresh())->not->toBeNull()
            ->and($designatore->fresh()->referee_id)->toBeNull();
    });
});

describe('first access', function () {
    test('a referee logs in with email and license number and must change the password', function () {
        createReferee();

        $this->post('/login', ['email' => 'luca@example.com', 'password' => '123456'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();

        foreach ([route('home'), route('profile.edit'), route('dashboard')] as $url) {
            $this->get($url)->assertRedirect(route('password.change'));
        }

        $this->get(route('password.change'))->assertOk()->assertSee('Scegli una nuova password');
    });

    test('changing the password unlocks the app and invalidates the license number', function () {
        $user = createReferee()->user;

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => '123456',
                'password' => 'nuova-password',
                'password_confirmation' => 'nuova-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        expect($user->fresh()->must_change_password)->toBeFalse();

        $this->get(route('home'))->assertOk();
        $this->post('/logout');

        $this->post('/login', ['email' => 'luca@example.com', 'password' => '123456']);
        $this->assertGuest();
    });

    test('the license number cannot be chosen as the new password', function () {
        $user = activeRefereeUser(['license_number' => '12345678']);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'password-personale',
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    });

    test('the emailed response links work before changing the password', function () {
        $referee = createReferee();
        $designation = createDesignation(User::factory()->create());
        $designation->update(['referee_id' => $referee->id]);

        $this->actingAs($referee->user)
            ->get($designation->fresh()->responseUrl('confirm'))
            ->assertOk();

        expect($designation->fresh()->status)->toBe('confirmed');
    });

    test('resetting a forgotten password replaces the license number', function () {
        Notification::fake();
        $user = createReferee()->user;

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'nuova-password',
                'password_confirmation' => 'nuova-password',
            ])->assertSessionHasNoErrors();

            return true;
        });

        expect($user->fresh()->must_change_password)->toBeFalse();
    });

    test('the password reset email is in italian', function () {
        $user = createReferee()->user;
        $notification = null;
        Notification::fake();

        $user->sendPasswordResetNotification('token');

        Notification::assertSentTo($user, ResetPassword::class, function ($sent) use (&$notification) {
            $notification = $sent;

            return true;
        });

        $mail = $notification->toMail($user);

        expect($mail->subject)->toBe('Recupero password')
            ->and($mail->actionText)->toBe('Scegli una nuova password');
    });
});

describe('access', function () {
    test('a referee is sent to the home instead of the staff dashboard', function () {
        $this->actingAs(activeRefereeUser())
            ->get(route('dashboard'))
            ->assertRedirect(route('home'));
    });

    test('a referee cannot open or change management pages', function () {
        $user = activeRefereeUser();
        $other = createReferee(['name' => 'Altro', 'email' => 'altro@example.com', 'license_number' => '2']);

        $this->actingAs($user);

        foreach (['referees.index', 'teams.index', 'venues.index', 'rugby-matches.index', 'designations.index', 'reports.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }

        $this->get(route('referees.show', $other))->assertForbidden();
        $this->post(route('pending-mails.send'))->assertForbidden();
        $this->post(route('referees.unavailabilities.store', $other), [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ])->assertForbidden();
        $this->delete(route('referees.destroy', $other))->assertForbidden();

        expect(Referee::find($other->id))->not->toBeNull()
            ->and($other->unavailabilities()->count())->toBe(0);
    });

    test('a referee does not see management links in the navigation', function () {
        $this->actingAs(activeRefereeUser())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Configurazioni')
            ->assertDontSee('/rugby-matches')
            ->assertDontSee('/reports');
    });

    test('designatori keep access to management pages', function () {
        $this->actingAs(User::factory()->create(['role' => 'delegato']))
            ->get(route('referees.index'))
            ->assertOk();
    });
});

describe('dashboard', function () {
    beforeEach(function () {
        // Mercoledì 7 ottobre 2026: la settimana va da lunedì 5 a domenica 11
        $this->travelTo(Carbon::parse('2026-10-07 10:00:00'));
    });

    test('a referee sees every designated name before thursday, even when not confirmed', function () {
        $designation = createDesignation(User::factory()->create());
        $designation->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);

        $this->actingAs(activeRefereeUser())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Mario Rossi')
            ->assertSee('In attesa')
            ->assertDontSee('Designazioni visibili dal giovedì');
    });

    test('guests still do not see names of unconfirmed designations', function () {
        $designation = createDesignation(User::factory()->create());
        $designation->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Mario Rossi')
            ->assertSee('In attesa di conferma');
    });

    test('the evaluator is hidden from guests and referees, also in the referee count, but not from the designatore', function () {
        $designatore = User::factory()->create(['role' => 'designatore']);
        $designation = createDesignation($designatore, ['status' => 'confirmed', 'role' => 'Valutatore']);
        $designation->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);
        $designation->referee->update(['name' => 'Vera Valutatrice']);

        // Giovedì: i visitatori vedono i nomi delle designazioni confermate
        $this->travelTo(Carbon::parse('2026-10-08 10:00:00'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Vera Valutatrice')
            ->assertDontSee('Valutatore')
            ->assertDontSee('1 arbitro')
            ->assertSee('Da designare');

        $this->actingAs(activeRefereeUser())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Vera Valutatrice')
            ->assertSee('Valutatore della prestazione designato')
            ->assertSee('Da designare');

        $this->actingAs($designatore->fresh())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Vera Valutatrice')
            ->assertSee('Valutatore')
            ->assertDontSee('Valutatore della prestazione designato');
    });

    test('from the day after the match referees see the evaluator name instead of the notice', function () {
        $designation = createDesignation(User::factory()->create(), ['status' => 'confirmed', 'role' => 'Valutatore']);
        $designation->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);
        $designation->referee->update(['name' => 'Vera Valutatrice']);
        $this->actingAs(activeRefereeUser());

        // Il giorno della partita, anche a gara finita, resta la scritta
        $this->travelTo(Carbon::parse('2026-10-11 23:59:00'));
        $this->get(route('home'))
            ->assertSee('Valutatore della prestazione designato')
            ->assertDontSee('Vera Valutatrice');

        $this->travelTo(Carbon::parse('2026-10-12 00:00:00'));
        $this->get(route('home', ['week' => '2026-10-05']))
            ->assertSee('Vera Valutatrice')
            ->assertSee('Valutatore')
            ->assertDontSee('Valutatore della prestazione designato');
    });

    test('after the match the evaluator stays hidden from guests', function () {
        // Venerdì: i visitatori vedono i nomi della settimana, compresa la partita di giovedì
        $designation = createDesignation(User::factory()->create(), ['status' => 'confirmed', 'role' => 'Valutatore']);
        $designation->match->update(['date_time' => Carbon::parse('2026-10-08 15:00:00')]);
        $designation->referee->update(['name' => 'Vera Valutatrice']);

        $this->travelTo(Carbon::parse('2026-10-09 10:00:00'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Vera Valutatrice')
            ->assertDontSee('Valutatore');
    });

    test('after the match referees do not see an evaluator who declined', function () {
        $designation = createDesignation(User::factory()->create(), ['status' => 'cancelled', 'role' => 'Valutatore']);
        $designation->match->update(['date_time' => Carbon::parse('2026-10-08 15:00:00')]);
        $designation->referee->update(['name' => 'Vera Valutatrice']);

        $this->travelTo(Carbon::parse('2026-10-09 10:00:00'));

        $this->actingAs(activeRefereeUser())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Vera Valutatrice')
            ->assertDontSee('Valutatore');
    });

    test('referees see no evaluator notice when none is designated or the evaluator declined', function () {
        $designatore = User::factory()->create();
        $declined = createDesignation($designatore, ['status' => 'cancelled', 'role' => 'Valutatore']);
        $declined->match->update(['date_time' => Carbon::parse('2026-10-11 15:00:00')]);
        $arbitro = createDesignation($designatore);
        $arbitro->match->update(['date_time' => Carbon::parse('2026-10-11 16:00:00')]);

        $this->actingAs(activeRefereeUser())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Valutatore della prestazione designato');
    });

    test('the text report leaves out the evaluator and still lists the other roles', function () {
        $designatore = User::factory()->create();
        $arbitro = createDesignation($designatore, ['status' => 'confirmed']);
        $valutatore = Designation::create([
            'match_id' => $arbitro->match_id,
            'referee_id' => createReferee(['name' => 'Vera Valutatrice'])->id,
            'role' => 'Valutatore',
            'assignment_date' => now(),
            'status' => 'confirmed',
        ]);

        $this->actingAs($designatore)
            ->get(route('reports.text', ['matches' => [$valutatore->match_id]]))
            ->assertOk()
            ->assertSee('Mario Rossi')
            ->assertSee('Accettate: 1')
            ->assertDontSee('Vera Valutatrice')
            ->assertDontSee('Valutatore');
    });

    test('the telegram message leaves out the evaluator', function () {
        config(['services.telegram.bot_token' => 'token', 'services.telegram.chat_id' => '1']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $designatore = User::factory()->create();
        $arbitro = createDesignation($designatore, ['status' => 'confirmed']);
        $valutatore = createDesignation($designatore, ['status' => 'confirmed', 'role' => 'Valutatore']);
        $valutatore->referee->update(['name' => 'Vera Valutatrice']);

        $this->actingAs($designatore)
            ->post(route('reports.telegram'), ['matches' => [$arbitro->match_id, $valutatore->match_id]])
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'], 'Mario Rossi')
            && ! str_contains($request['text'], 'Vera Valutatrice')
            && ! str_contains($request['text'], 'Valutatore'));
    });

    test('a referee can browse past and future weeks', function () {
        $past = createDesignation(User::factory()->create());
        $past->match->update(['date_time' => Carbon::parse('2026-09-27 15:00:00'), 'status' => 'completed']);
        $future = createDesignation(User::factory()->create());
        $future->match->update(['date_time' => Carbon::parse('2026-10-18 15:00:00')]);

        $user = activeRefereeUser();

        $this->actingAs($user)
            ->get(route('home', ['week' => '2026-09-23']))
            ->assertOk()
            ->assertSee('dal 21/09/2026 al 27/09/2026')
            ->assertSee('Mario Rossi')
            ->assertSee('week=2026-09-14', false)
            ->assertSee('week=2026-09-28', false);

        $this->get(route('home', ['week' => '2026-10-12']))
            ->assertOk()
            ->assertSee('dal 12/10/2026 al 18/10/2026')
            ->assertSee('Mario Rossi');

        $this->get(route('home', ['week' => '2026-10-05']))
            ->assertOk()
            ->assertDontSee('Mario Rossi');
    });

    test('an invalid week falls back to the current one', function () {
        $this->actingAs(activeRefereeUser());

        foreach (['2026-02-31', 'domani', '2026-1-1'] as $week) {
            $this->get(route('home', ['week' => $week]))
                ->assertOk()
                ->assertSee('dal 05/10/2026 al 11/10/2026');
        }
    });

    test('guests cannot browse other weeks', function () {
        $designation = createDesignation(User::factory()->create(), ['status' => 'confirmed']);
        $designation->match->update(['date_time' => Carbon::parse('2026-10-18 15:00:00')]);

        $this->get(route('home', ['week' => '2026-10-12']))
            ->assertOk()
            ->assertSee('dal 05/10/2026 al 11/10/2026')
            ->assertDontSee('Mario Rossi')
            ->assertDontSee('Settimana successiva');
    });
});

describe('profile', function () {
    test('a referee updates city and phone but not name or license', function () {
        $user = activeRefereeUser();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nome Falso',
                'email' => $user->email,
                'phone' => '333123',
                'city' => 'Pescara',
                'license_number' => '999',
            ])
            ->assertSessionHasNoErrors();

        expect($user->referee->fresh())
            ->phone->toBe('333123')
            ->city->toBe('Pescara')
            ->name->toBe('Luca Bianchi')
            ->license_number->toBe('123456');
    });

    test('a referee cannot delete the account', function () {
        $user = activeRefereeUser();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password-personale'])
            ->assertForbidden();

        $this->get(route('profile.edit'))->assertOk()->assertDontSee('Elimina account');
    });

    test('a new email applies only after confirming the link, then becomes the login', function () {
        Mail::fake();
        $user = activeRefereeUser();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['email' => 'Nuova@Example.com'])
            ->assertSessionHasNoErrors();

        Mail::assertSent(EmailChangeConfirmationMail::class, fn ($mail) => $mail->hasTo('nuova@example.com'));

        expect($user->fresh())
            ->email->toBe('luca@example.com')
            ->pending_email->toBe('nuova@example.com');

        $this->post('/logout');

        $this->get(confirmationUrl($user, 'nuova@example.com'))->assertRedirect(route('login'));

        expect($user->fresh())
            ->email->toBe('nuova@example.com')
            ->pending_email->toBeNull()
            ->and($user->referee->fresh()->email)->toBe('nuova@example.com');

        $this->post('/login', ['email' => 'luca@example.com', 'password' => 'password-personale']);
        $this->assertGuest();

        $this->post('/login', ['email' => 'nuova@example.com', 'password' => 'password-personale']);
        $this->assertAuthenticated();
    });

    test('the confirmation mail renders a working signed link', function () {
        $user = activeRefereeUser();
        $user->update(['pending_email' => 'nuova@example.com']);

        $html = (new EmailChangeConfirmationMail($user, 'nuova@example.com'))->render();

        preg_match('/href="([^"]+profile\/email\/confirm[^"]+)"/', $html, $matches);

        $this->get(html_entity_decode($matches[1]))->assertRedirect();

        expect($user->fresh()->email)->toBe('nuova@example.com');
    });

    test('tampered, superseded or already used links change nothing', function () {
        $user = activeRefereeUser();
        $user->update(['pending_email' => 'seconda@example.com']);

        $this->get(route('profile.email.confirm', ['user' => $user->id, 'email' => 'seconda@example.com']))
            ->assertForbidden();

        $this->get(confirmationUrl($user, 'prima@example.com'))->assertRedirect();
        expect($user->fresh()->email)->toBe('luca@example.com');

        $this->get(confirmationUrl($user, 'seconda@example.com'))->assertRedirect();
        expect($user->fresh()->email)->toBe('seconda@example.com');

        $this->get(confirmationUrl($user, 'seconda@example.com'))->assertSessionHas('error');
    });

    test('an expired link changes nothing', function () {
        $user = activeRefereeUser();
        $user->update(['pending_email' => 'nuova@example.com']);
        $url = confirmationUrl($user, 'nuova@example.com');

        $this->travel(2)->hours();

        $this->get($url)->assertForbidden();
        expect($user->fresh()->email)->toBe('luca@example.com');
    });

    test('an email taken by another account is refused, also if taken after the request', function () {
        Mail::fake();
        $user = activeRefereeUser();
        User::factory()->create(['email' => 'occupata@example.com']);

        $this->actingAs($user)
            ->patch(route('profile.update'), ['email' => 'occupata@example.com'])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();

        $user->update(['pending_email' => 'libera@example.com']);
        User::factory()->create(['email' => 'libera@example.com']);

        $this->get(confirmationUrl($user, 'libera@example.com'))->assertSessionHas('error');
        expect($user->fresh()->email)->toBe('luca@example.com');
    });

    test('a failed confirmation mail leaves no pending email', function () {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));
        $user = activeRefereeUser();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['email' => 'nuova@example.com'])
            ->assertSessionHas('error');

        expect($user->fresh()->pending_email)->toBeNull();
    });

    test('a designatore without referee sheet still changes email immediately', function () {
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'Nome', 'email' => 'subito@example.com'])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->email)->toBe('subito@example.com');
        Mail::assertNothingSent();
    });

    test('a designatore linked to a referee sheet keeps name editing and confirms the email', function () {
        Mail::fake();
        $designatore = User::factory()->create(['email' => 'luca@example.com', 'role' => 'designatore']);
        $referee = createReferee();

        $this->actingAs($designatore->fresh())
            ->patch(route('profile.update'), ['name' => 'Nuovo Nome', 'email' => 'nuova@example.com', 'city' => 'Chieti'])
            ->assertSessionHasNoErrors();

        expect($designatore->fresh())->name->toBe('Nuovo Nome')->email->toBe('luca@example.com')
            ->and($referee->fresh()->city)->toBe('Chieti');
    });
});

describe('unavailabilities', function () {
    test('a referee adds and removes own periods', function () {
        Mail::fake();
        $user = activeRefereeUser();

        $this->actingAs($user)
            ->post(route('profile.unavailabilities.store'), [
                'start_date' => today()->addDay()->toDateString(),
                'end_date' => today()->addDays(3)->toDateString(),
            ])
            ->assertRedirect(route('profile.unavailabilities.index'))
            ->assertSessionHas('success');

        Mail::assertNothingSent();

        $period = $user->referee->unavailabilities()->sole();

        $this->get(route('profile.unavailabilities.index'))->assertSee($period->start_date->format('d/m/Y'));

        $this->delete(route('profile.unavailabilities.destroy', $period->id))->assertRedirect(route('profile.unavailabilities.index'));

        expect(RefereeUnavailability::find($period->id))->toBeNull();
    });

    test('the menu entry sits between profile and logout for referees and designatori with a sheet', function () {
        $designatore = User::factory()->create(['email' => 'designatore@example.com', 'role' => 'designatore']);
        createReferee(['email' => 'designatore@example.com', 'license_number' => '321']);

        foreach ([activeRefereeUser(), $designatore->fresh()] as $user) {
            $this->actingAs($user)
                ->get(route('home'))
                ->assertOk()
                ->assertSeeInOrder(['Profilo e password', 'Indisponibilità', 'Esci']);

            $this->get(route('profile.unavailabilities.index'))->assertOk();
            $this->get(route('profile.edit'))->assertOk()->assertDontSee('Nei periodi indicati');
        }
    });

    test('a designatore without referee sheet has neither the menu entry nor the page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertDontSee('Indisponibilità');
        $this->get(route('profile.unavailabilities.index'))->assertForbidden();
    });

    test('a fuori ruolo referee manages unavailabilities for tutor and evaluator activity', function () {
        $user = activeRefereeUser(['license_level' => Referee::FUORI_RUOLO, 'is_evaluator' => true]);

        $this->actingAs($user)
            ->get(route('profile.unavailabilities.index'))
            ->assertOk()
            ->assertSee('non potrai essere designato come Tutor o Valutatore');

        $this->post(route('profile.unavailabilities.store'), [
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
        ])->assertSessionHas('success');

        expect($user->referee->unavailabilities()->count())->toBe(1);
    });

    test('a referee cannot remove the period of another referee', function () {
        $other = createReferee(['name' => 'Altro', 'email' => 'altro@example.com', 'license_number' => '2']);
        $period = $other->unavailabilities()->create(['start_date' => today(), 'end_date' => today()->addDay()]);

        $this->actingAs(activeRefereeUser())
            ->delete(route('profile.unavailabilities.destroy', $period->id))
            ->assertNotFound();

        expect(RefereeUnavailability::find($period->id))->not->toBeNull();
    });

    test('periods already ended or with inverted dates are refused', function () {
        $user = activeRefereeUser();

        $this->actingAs($user)
            ->post(route('profile.unavailabilities.store'), [
                'start_date' => today()->subDays(3)->toDateString(),
                'end_date' => today()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('end_date');

        $this->post(route('profile.unavailabilities.store'), [
            'start_date' => today()->addDays(3)->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
        ])->assertSessionHasErrors('end_date');

        expect($user->referee->unavailabilities()->count())->toBe(0);
    });

    test('a user without referee sheet has no unavailabilities to manage', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('profile.unavailabilities.store'), [
                'start_date' => today()->toDateString(),
                'end_date' => today()->toDateString(),
            ])
            ->assertForbidden();
    });

    test('a period covering a designated match warns the referee and the designatore, keeping the designation', function () {
        Mail::fake();
        $designatore = User::factory()->create(['email' => 'designatore@example.com']);
        $user = activeRefereeUser();
        $designation = createDesignation($designatore);
        $designation->update(['referee_id' => $user->referee_id]);

        $date = Carbon::parse($designation->match->date_time)->toDateString();

        $this->actingAs($user)
            ->post(route('profile.unavailabilities.store'), ['start_date' => $date, 'end_date' => $date])
            ->assertSessionHas('error');

        Mail::assertSent(RefereeUnavailabilityConflictMail::class, fn ($mail) => $mail->hasTo('designatore@example.com'));

        expect(Designation::find($designation->id))->not->toBeNull()
            ->and($user->referee->unavailabilities()->count())->toBe(1);
    });

    test('the conflict mail renders the match', function () {
        $designatore = User::factory()->create();
        $user = activeRefereeUser();
        $designation = createDesignation($designatore);
        $period = $user->referee->unavailabilities()->create(['start_date' => today(), 'end_date' => today()]);

        $html = (new RefereeUnavailabilityConflictMail($user->referee, $period, collect([$designation->load('match')])))->render();

        expect($html)->toContain('Luca Bianchi')->toContain($designation->match->label);
    });

    test('declined designations and cancelled matches do not trigger the warning', function () {
        Mail::fake();
        $designatore = User::factory()->create();
        $user = activeRefereeUser();

        $declined = createDesignation($designatore, ['status' => 'cancelled']);
        $declined->update(['referee_id' => $user->referee_id]);
        $cancelledMatch = createDesignation($designatore);
        $cancelledMatch->update(['referee_id' => $user->referee_id]);
        RugbyMatch::whereKey($cancelledMatch->match_id)->update(['status' => 'cancelled']);

        $date = Carbon::parse($declined->match->date_time)->toDateString();

        $this->actingAs($user)
            ->post(route('profile.unavailabilities.store'), ['start_date' => $date, 'end_date' => $date])
            ->assertSessionHas('success');

        Mail::assertNothingSent();
    });
});
