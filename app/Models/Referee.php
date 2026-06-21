<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'email',
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

    // A referee can have many designations
    public function designations()
    {
        return $this->hasMany(Designation::class);
    }
}
