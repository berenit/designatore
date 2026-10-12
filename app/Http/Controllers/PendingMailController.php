<?php

namespace App\Http\Controllers;

use App\Support\PendingMails;
use Illuminate\Support\Facades\Redirect;

class PendingMailController extends Controller
{
    /** Invia le email in attesa generate dalle ultime modifiche. */
    public function send()
    {
        try {
            $sent = PendingMails::send();
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()->with('error', 'Invio non riuscito: '.PendingMails::count().' email restano in attesa. Riprova.');
        }

        return Redirect::back()->with('success', $sent === 1 ? 'Email inviata.' : "{$sent} email inviate.");
    }

    /** Scarta le email in attesa senza inviarle. */
    public function discard()
    {
        $discarded = PendingMails::discard();

        return Redirect::back()->with('success', $discarded === 1 ? 'Invio dell\'email annullato.' : "Invio di {$discarded} email annullato.");
    }
}
