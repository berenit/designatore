<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'referee_id',
    'start_date',
    'end_date',
])]
class RefereeUnavailability extends Model
{
    use MassPrunable;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /** Periodi ormai conclusi, eliminati ogni giorno da model:prune (vedi routes/console.php). */
    public function prunable(): Builder
    {
        return static::whereDate('end_date', '<', today());
    }

    public function referee()
    {
        return $this->belongsTo(Referee::class);
    }
}
