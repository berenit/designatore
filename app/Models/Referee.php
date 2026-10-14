<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'email',
    'license_number',
    'phone',
    'license_level',
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
    ];

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
