<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email generate da una modifica (designazioni, partite) in attesa che il designatore
 * ne confermi o annulli l'invio. Restano in sessione finché non viene presa una decisione.
 */
class PendingMails
{
    private const SESSION_KEY = 'pending_mails';

    /**
     * Mette in attesa un'email. Il mailable viene serializzato subito, così l'email rispecchia
     * lo stato al momento della modifica anche se i modelli cambiano dopo (o vengono eliminati).
     * Dentro una transazione l'aggiunta avviene solo al commit: un rollback non lascia email orfane.
     */
    public static function add(string $to, Mailable $mail): void
    {
        $entry = [
            'to' => $to,
            'subject' => $mail->envelope()->subject,
            'payload' => Crypt::encryptString(serialize($mail)),
        ];

        DB::afterCommit(fn () => session()->push(self::SESSION_KEY, $entry));
    }

    /** Email in attesa, senza il payload (per mostrarle all'utente). */
    public static function all(): array
    {
        return array_map(
            fn ($entry) => ['to' => $entry['to'], 'subject' => $entry['subject']],
            session(self::SESSION_KEY, [])
        );
    }

    public static function count(): int
    {
        return count(session(self::SESSION_KEY, []));
    }

    /**
     * Invia tutte le email in attesa e ritorna quante ne sono partite. Le email già inviate
     * vengono tolte subito dalla coda: se un invio fallisce, restano in attesa solo le altre.
     */
    public static function send(): int
    {
        $sent = 0;

        while ($entry = session(self::SESSION_KEY, [])[0] ?? null) {
            Mail::to($entry['to'])->send(unserialize(Crypt::decryptString($entry['payload'])));

            session()->put(self::SESSION_KEY, array_slice(session(self::SESSION_KEY), 1));
            $sent++;

            Log::info('Email inviata dopo conferma del designatore', [
                'to' => $entry['to'],
                'subject' => $entry['subject'],
            ]);
        }

        session()->forget(self::SESSION_KEY);

        return $sent;
    }

    /** Scarta le email in attesa senza inviarle e ritorna quante erano. */
    public static function discard(): int
    {
        $entries = session()->pull(self::SESSION_KEY, []);

        foreach ($entries as $entry) {
            Log::info('Invio email annullato dal designatore', [
                'to' => $entry['to'],
                'subject' => $entry['subject'],
            ]);
        }

        return count($entries);
    }
}
