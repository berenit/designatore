<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'date_time',
    'venue',
    'home_team_id',
    'away_team_id',
    'competition_type',
    'status'
])]
class RugbyMatch extends Model
{
    protected $table = 'matches';
    // A match belongs to a home team
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    // A match belongs to an away team
    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    // A match has one designation
    public function designation()
    {
        return $this->hasOne(Designation::class, 'match_id');
    }
}
