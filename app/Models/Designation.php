<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'match_id',
    'referee_id',
    'role',
    'assigned_by',
    'assignment_date',
    'status',
    'notes',
])]
class Designation extends Model
{
    /** Ruoli assegnabili a una designazione, nell'ordine canonico di visualizzazione/gestione. */
    public const ROLES = [
        'Arbitro', 'Assistente 1', 'Assistente 2', '4° uomo', '5° uomo', 'Direttore di concentramento', 'Osservatore', 'Tutor',
    ];

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
