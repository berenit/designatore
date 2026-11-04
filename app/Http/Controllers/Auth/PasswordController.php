<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\NotLicenseNumber;
use App\Support\PasswordMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Pagina del cambio obbligatorio al primo accesso.
     */
    public function change(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('profile.edit');
        }

        return view('auth.change-password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password', new NotLicenseNumber($user)],
        ], PasswordMessages::VALIDATION);

        $wasFirstAccess = $user->must_change_password;

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        if ($wasFirstAccess) {
            return redirect()->route('home')->with('success', 'Password aggiornata.');
        }

        return back()->with('status', 'password-updated');
    }
}
