<?php

namespace App\Models;

use App\Mail\DesignationRemovedMail;
use App\Support\PendingMails;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

#[Fillable([
    'match_id',
    'referee_id',
    'role',
    'assigned_by',
    'assignment_date',
    'status',
    'notes',
    'reminder_sent_at',
    'notified_at',
    'reminders_sent',
    'escalated_at',
])]
class Designation extends Model
{
    protected function casts(): array
    {
        return [
            'reminder_sent_at' => 'datetime',
            'notified_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    /** Ruoli assegnabili a una designazione, nell'ordine canonico di visualizzazione/gestione. */
    public const ROLES = [
        'Arbitro', 'Assistente 1', 'Assistente 2', '4° uomo', '5° uomo', 'Direttore di concentramento', 'Valutatore', 'Tutor',
    ];

    /** Ruolo riservato agli arbitri abilitati come Valutatori (vedi Referee::canBeDesignatedAs()). */
    public const EVALUATOR_ROLE = 'Valutatore';

    /** Etichette italiane per lo stato di una designazione (usate in tutta l'app). */
    public const STATUS_LABELS = [
        'pending' => 'In attesa',
        'confirmed' => 'Accettata',
        'completed' => 'Completata',
        'cancelled' => 'Annullata',
    ];

    /** Campi che azzerano il ciclo dei solleciti: da usare quando serve una nuova conferma (email non ancora partita). */
    public const CONFIRMATION_RESET = ['notified_at' => null, 'reminders_sent' => 0, 'escalated_at' => null];

    /** Intervallo tra un sollecito e l'altro, in secondi (vedi config/designations.php). */
    public static function reminderIntervalSeconds(): int
    {
        return max(1, (int) round(config('designations.reminder_interval_minutes') * 60));
    }

    /** Tempo concesso per rispondere prima di avvisare il designatore, in secondi. */
    public static function responseDeadlineSeconds(): int
    {
        return max(1, (int) round(config('designations.response_deadline_minutes') * 60));
    }

    /** Numero di solleciti entro la scadenza (default: 5, a 8/16/24/32/40 ore). */
    public static function maxReminders(): int
    {
        return max(0, (int) ceil(self::responseDeadlineSeconds() / self::reminderIntervalSeconds()) - 1);
    }

    /** Prefisso dell'oggetto per il sollecito n-esimo ("SOLLECITO 1", ..., "ULTIMO SOLLECITO"). */
    public static function reminderLabel(int $number): string
    {
        return $number >= self::maxReminders() ? 'ULTIMO SOLLECITO' : "SOLLECITO {$number}";
    }

    /**
     * Segna come inviata ora la richiesta di conferma della designazione (avvia il ciclo dei solleciti),
     * purché sia ancora in attesa e assegnata allo stesso arbitro a cui è partita l'email.
     */
    public static function markConfirmationRequested(int $designationId, int $refereeId): void
    {
        static::whereKey($designationId)
            ->where('referee_id', $refereeId)
            ->where('status', 'pending')
            ->update(['notified_at' => now(), 'reminders_sent' => 0, 'escalated_at' => null]);
    }

    /**
     * Link firmato con cui l'arbitro risponde ('confirm' o 'decline'). Riporta l'arbitro destinatario:
     * se la designazione passa a un altro arbitro, il link di quello precedente non vale più.
     */
    public function responseUrl(string $action): string
    {
        return URL::signedRoute('designations.respond', [
            'designation' => $this->id,
            'action' => $action,
            'referee' => $this->referee_id,
        ]);
    }

    /**
     * Elimina la designazione: i link già inviati smettono di valere. Se l'arbitro non l'aveva
     * già rifiutata, mette in attesa di conferma l'email che lo avvisa della rimozione.
     */
    public function removeAndNotifyReferee(): void
    {
        $this->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee']);

        if ($this->status !== 'cancelled') {
            PendingMails::add($this->referee->email, new DesignationRemovedMail($this));

            Log::info('Email di rimozione designazione in attesa di conferma per l\'arbitro', [
                'designation_id' => $this->id,
                'match_id' => $this->match_id,
                'referee_email' => $this->referee->email,
            ]);
        }

        $this->delete();
    }

    /** Etichetta italiana dello stato (per le viste). */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    // A designation belongs to a match
    public function match()
    {
        return $this->belongsTo(RugbyMatch::class);
    }

    // A designation belongs to a referee
    public function referee()
    {
        return $this->belongsTo(Referee::class);
    }

    // A designation belongs to a user (who assigned it)
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
