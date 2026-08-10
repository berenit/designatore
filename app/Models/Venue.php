<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'city',
    'address',
    'contact_person',
    'contact_phone',
])]
class Venue extends Model
{
    // Un campo può ospitare molte gare
    public function matches()
    {
        return $this->hasMany(RugbyMatch::class, 'venue_id');
    }

    /** Etichetta leggibile per le combo box (es. "Stadio Comunale — Chieti"). */
    public function getLabelAttribute(): string
    {
        return "{$this->name} — {$this->city}";
    }
}
