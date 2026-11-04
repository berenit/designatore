<?php

namespace App\Support;

use Illuminate\Support\Facades\Password;

/** Messaggi in italiano per cambio e recupero password, indipendenti dalla lingua configurata. */
class PasswordMessages
{
    public const VALIDATION = [
        'current_password.required' => 'Inserisci la password attuale.',
        'current_password.current_password' => 'La password attuale non è corretta.',
        'email.required' => 'Inserisci l\'indirizzo email.',
        'email.email' => 'Inserisci un indirizzo email valido.',
        'password.required' => 'Inserisci la nuova password.',
        'password.min' => 'La nuova password deve contenere almeno :min caratteri.',
        'password.confirmed' => 'Le due password non coincidono.',
        'password.different' => 'La nuova password deve essere diversa da quella attuale.',
    ];

    /** Traduce l'esito restituito dal broker di recupero password. */
    public static function status(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => 'Ti abbiamo inviato il link per scegliere una nuova password.',
            Password::PASSWORD_RESET => 'Password aggiornata. Ora puoi accedere.',
            Password::INVALID_USER => 'Nessun account corrisponde a questa email.',
            Password::RESET_THROTTLED => 'Attendi qualche minuto prima di riprovare.',
            Password::INVALID_TOKEN => 'Il link per scegliere la password non è valido o è scaduto.',
            default => 'Operazione non riuscita. Riprova.',
        };
    }
}
