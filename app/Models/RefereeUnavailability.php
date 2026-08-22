<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'referee_id',
    'start_date',
    'end_date',
])]
class RefereeUnavailability extends Model
{
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function referee()
    {
        return $this->belongsTo(Referee::class);
    }
}
