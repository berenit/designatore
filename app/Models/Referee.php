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

    // A referee can have many designations
    public function designations()
    {
        return $this->hasMany(Designation::class);
    }
}
