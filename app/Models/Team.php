<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'city',
    'league_division',
    'contact_person',
    'contact_email',
    'contact_phone',
])]
class Team extends Model
{
    // A team can have many home matches
    public function homeMatches()
    {
        return $this->hasMany(RugbyMatch::class, 'home_team_id');
    }

    // A team can have many away matches
    public function awayMatches()
    {
        return $this->hasMany(RugbyMatch::class, 'away_team_id');
    }
}
