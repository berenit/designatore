<?php

namespace App\Http\Controllers;

use App\Support\PendingMails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PendingMailController extends Controller
{
    /** Invia le email selezionate tra quelle in attesa e scarta le altre proposte nel modale. */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'offered' => ['required', 'array'],
            'offered.*' => ['string'],
            'selected' => ['array'],
            'selected.*' => ['string'],
        ]);

        try {
            $sent = PendingMails::send($validated['selected'] ?? [], $validated['offered']);
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()->with('error', 'Invio non riuscito: '.PendingMails::count().' email restano in attesa. Riprova.');
        }

        return Redirect::back()->with('success', match ($sent) {
            0 => 'Nessuna email inviata.',
            1 => 'Email inviata.',
            default => "{$sent} email inviate.",
        });
    }

    /** Scarta le email in attesa senza inviarle. */
    public function discard()
    {
        $discarded = PendingMails::discard();

        return Redirect::back()->with('success', $discarded === 1 ? 'Invio dell\'email annullato.' : "Invio di {$discarded} email annullato.");
    }
}
