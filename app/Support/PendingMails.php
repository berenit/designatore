<?php

namespace App\Support;

use App\Mail\RequestsConfirmation;
use App\Models\Designation;
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
            fn ($entry) => ['id' => self::id($entry), 'to' => $entry['to'], 'subject' => $entry['subject']],
            session(self::SESSION_KEY, [])
        );
    }

    public static function count(): int
    {
        return count(session(self::SESSION_KEY, []));
    }

    /**
     * Applica la scelta del designatore e ritorna quante email sono partite: invia le selezionate
     * e scarta quelle che gli erano state proposte ma non ha selezionato. Le email comparse in coda
     * dopo la scelta (es. da un'altra scheda) non sono state viste da lui, quindi restano in attesa.
     * Le email già inviate vengono tolte subito: se un invio fallisce, restano solo le altre selezionate.
     *
     * @param  array<string>  $selectedIds
     * @param  array<string>  $offeredIds
     */
    public static function send(array $selectedIds, array $offeredIds): int
    {
        $entries = session(self::SESSION_KEY, []);

        foreach ($entries as $entry) {
            $id = self::id($entry);

            if (in_array($id, $offeredIds, true) && ! in_array($id, $selectedIds, true)) {
                self::remove($entry);
                self::logDiscarded($entry);
            }
        }

        $sent = 0;

        foreach ($entries as $entry) {
            if (! in_array(self::id($entry), $selectedIds, true)) {
                continue;
            }

            $mail = unserialize(Crypt::decryptString($entry['payload']));
            Mail::to($entry['to'])->send($mail);

            // Una richiesta di conferma partita davvero avvia il ciclo dei solleciti
            if ($mail instanceof RequestsConfirmation) {
                $designation = $mail->confirmationDesignation();
                Designation::markConfirmationRequested($designation->id, $designation->referee_id);
            }

            self::remove($entry);
            $sent++;

            Log::info('Email inviata dopo conferma del designatore', [
                'to' => $entry['to'],
                'subject' => $entry['subject'],
            ]);
        }

        return $sent;
    }

    /** Scarta le email in attesa senza inviarle e ritorna quante erano. */
    public static function discard(): int
    {
        $entries = session()->pull(self::SESSION_KEY, []);

        array_walk($entries, fn ($entry) => self::logDiscarded($entry));

        return count($entries);
    }

    /** Identificativo stabile di un'email in coda: il payload cifrato è unico per ogni email. */
    private static function id(array $entry): string
    {
        return hash('sha256', $entry['payload']);
    }

    private static function remove(array $entry): void
    {
        session()->put(self::SESSION_KEY, array_values(array_filter(
            session(self::SESSION_KEY, []),
            fn ($other) => $other['payload'] !== $entry['payload']
        )));
    }

    private static function logDiscarded(array $entry): void
    {
        Log::info('Invio email annullato dal designatore', [
            'to' => $entry['to'],
            'subject' => $entry['subject'],
        ]);
    }
}
