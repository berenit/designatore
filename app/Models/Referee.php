<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'license_number',
    'phone',
    'city',
    'license_level',
    'is_evaluator',
    'availability_status',
])]
class Referee extends Model
{
    /**
     * Categorie arbitrali previste.
     */
    public const CATEGORIES = [
        'Derogato',
        'Regionale',
        'Nazionale serie B',
        'Nazionale serie A',
        'Nazionale serie A Elite',
        'Assistente serie A',
        'Assistente serie A Elite',
        self::FUORI_RUOLO,
    ];

    /** Categoria degli arbitri non designabili, se non come Valutatori (se abilitati). */
    public const FUORI_RUOLO = 'Fuori Ruolo';

    protected $casts = [
        'is_evaluator' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $referee) => $referee->syncAccount());

        // Un designatore che è anche arbitro conserva l'account: si perde solo il collegamento alla scheda.
        static::deleting(fn (self $referee) => $referee->user()->where('role', User::ROLE_REFEREE)->delete());
    }

    /**
     * Allinea l'account di accesso alla scheda: l'email della scheda è anche il nome utente.
     * L'account si crea alla prima scheda con tessera, che fa da prima password.
     */
    public function syncAccount(): void
    {
        $user = $this->user()->first();

        if (! $user) {
            $existing = User::whereNull('referee_id')->whereRaw('LOWER(email) = ?', [Str::lower($this->email)])->first();

            if ($existing) {
                $existing->update(['referee_id' => $this->id]);
            } elseif (filled($this->license_number)) {
                User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => User::ROLE_REFEREE,
                    'referee_id' => $this->id,
                    'password' => $this->license_number,
                    'must_change_password' => true,
                ]);
            }

            return;
        }

        $user->email = $this->email;

        if ($user->isDirty('email')) {
            $user->pending_email = null;
        }

        if ($user->isReferee()) {
            $user->name = $this->name;
        }

        // Tessera corretta prima del primo accesso: la prima password resta il numero di tessera
        if ($user->must_change_password && $this->wasChanged('license_number')) {
            $user->password = $this->license_number;
        }

        $user->save();
    }

    // Account di accesso dell'arbitro
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Etichette italiane per lo stato di disponibilità.
     */
    public const AVAILABILITY_LABELS = [
        'available' => 'Disponibile',
        'limited' => 'Limitata',
        'unavailable' => 'Non disponibile',
    ];

    /** Etichetta italiana della disponibilità (per le viste). */
    public function getAvailabilityLabelAttribute(): string
    {
        return self::AVAILABILITY_LABELS[$this->availability_status] ?? $this->availability_status;
    }

    /** Numero di tessera da affiancare al nome nei report, es. "(Tess. 111111)"; vuoto se manca la tessera. */
    public function getLicenseLabelAttribute(): string
    {
        return filled($this->license_number) ? "(Tess. {$this->license_number})" : '';
    }

    /** Vero se l'arbitro è di categoria Derogato (evidenziato nelle scelte arbitro). */
    public function isDerogato(): bool
    {
        return $this->license_level === 'Derogato';
    }

    /** Vero se l'arbitro è di categoria Fuori Ruolo (designabile solo come Valutatore). */
    public function isFuoriRuolo(): bool
    {
        return $this->license_level === self::FUORI_RUOLO;
    }

    /** Valutatore: solo gli abilitati (anche se Fuori Ruolo); altri ruoli: tutti tranne i Fuori Ruolo. */
    public function canBeDesignatedAs(string $role): bool
    {
        return $role === Designation::EVALUATOR_ROLE ? $this->is_evaluator : ! $this->isFuoriRuolo();
    }

    // A referee can have many designations
    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    // Periodi di indisponibilità dell'arbitro
    public function unavailabilities()
    {
        return $this->hasMany(RefereeUnavailability::class);
    }

    /** Vero se l'arbitro è indisponibile nella data indicata (confronto per sola data). */
    public function isUnavailableOn(\DateTimeInterface $date): bool
    {
        $date = Carbon::parse($date)->startOfDay();

        return $this->unavailabilities
            ->contains(fn ($period) => $date->between($period->start_date, $period->end_date));
    }
}
