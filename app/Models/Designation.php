<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'match_id',
    'referee_id',
    'assigned_by',
    'assignment_date',
    'status',
    'notes'
])]
class Designation extends Model
{
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
