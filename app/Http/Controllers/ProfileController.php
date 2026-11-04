<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\EmailChangeConfirmationMail;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'referee' => $user->referee,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->referee) {
            return $this->updateLinkedProfile($request, $user);
        }

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Per chi ha una scheda arbitro i dati vivono sulla scheda. L'email riceve anche le
     * designazioni, quindi cambia solo dopo la conferma dal link inviato al nuovo indirizzo.
     */
    private function updateLinkedProfile(ProfileUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (! $user->isReferee()) {
            $user->update(['name' => $validated['name']]);
        }

        $user->referee->update([
            'phone' => $validated['phone'] ?? null,
            'city' => $validated['city'] ?? null,
        ]);

        // Rimettere l'indirizzo attuale annulla un'eventuale modifica in attesa
        if (Str::lower($validated['email']) === Str::lower($user->email)) {
            $user->update(['pending_email' => null]);

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        $user->update(['pending_email' => $validated['email']]);

        try {
            Mail::to($validated['email'])->send(new EmailChangeConfirmationMail($user, $validated['email']));
        } catch (\Throwable $e) {
            report($e);
            $user->update(['pending_email' => null]);

            return Redirect::route('profile.edit')
                ->with('error', 'Non è stato possibile inviare il link di conferma: l\'email non è stata cambiata.');
        }

        return Redirect::route('profile.edit')
            ->with('success', "Ti abbiamo inviato un link a {$validated['email']}: l'email cambierà solo dopo la conferma.");
    }

    /**
     * Applica la nuova email dal link firmato: da qui diventa anche il nome utente per il login.
     */
    public function confirmEmail(Request $request, User $user): RedirectResponse
    {
        $email = (string) $request->query('email');
        $target = Auth::id() === $user->id ? 'profile.edit' : 'login';

        // Un link superato da una richiesta più recente, o già usato, non deve cambiare nulla
        if (! $user->referee || $user->pending_email !== $email) {
            return Redirect::route($target)->with('error', 'Questo link di conferma non è più valido.');
        }

        // L'indirizzo potrebbe essere stato assegnato ad altri dopo la richiesta
        $taken = User::whereKeyNot($user->id)->where('email', $email)->exists()
            || Referee::whereKeyNot($user->referee_id)->where('email', $email)->exists();

        if ($taken) {
            $user->update(['pending_email' => null]);

            return Redirect::route($target)->with('error', 'Questo indirizzo email è già usato da un altro account.');
        }

        $user->referee->update(['email' => $email]);

        return Redirect::route($target)->with('success', 'Email aggiornata: da ora usala per accedere.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_if($request->user()->isReferee(), 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
