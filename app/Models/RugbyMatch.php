<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date_time',
    'venue',
    'name',
    'home_team_id',
    'away_team_id',
    'competition_type',
    'status',
])]
class RugbyMatch extends Model
{
    protected $table = 'matches';

    protected $casts = [
        'date_time' => 'datetime',
    ];

    /** Tutti i tipi di competizione ammessi. */
    public const COMPETITION_TYPES = [
        'League', 'Cup', 'Friendly', 'International', 'Tournament', 'Concentramento', 'Torneo',
    ];

    /** Tipi che coinvolgono 3+ squadre (gestiti via pivot match_team). */
    public const MULTI_TEAM_TYPES = ['Concentramento', 'Torneo'];

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

    // Squadre partecipanti per Concentramenti e Tornei (pivot)
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'match_team', 'match_id', 'team_id');
    }

    // A match can have many designations (uno per ruolo nelle gare singole, più arbitri negli eventi)
    public function designations()
    {
        return $this->hasMany(Designation::class, 'match_id');
    }

    /** Vero se l'evento coinvolge più squadre (Concentramento / Torneo). */
    public function isMultiTeam(): bool
    {
        return in_array($this->competition_type, self::MULTI_TEAM_TYPES, true);
    }

    /** Squadre coinvolte: pivot per gli eventi multi-squadra, casa+ospite per le partite singole. */
    public function participatingTeams()
    {
        if ($this->isMultiTeam()) {
            return $this->teams;
        }

        return collect([$this->homeTeam, $this->awayTeam])->filter()->values();
    }

    /** Etichetta leggibile dell'incontro per liste, report ed email. */
    public function getLabelAttribute(): string
    {
        if ($this->isMultiTeam()) {
            if ($this->name) {
                return $this->name;
            }

            // Evita di idratare l'intera relazione (e l'N+1) se 'teams' non è già caricata
            $count = $this->relationLoaded('teams') ? $this->teams->count() : $this->teams()->count();

            return $this->competition_type.' · '.$count.' squadre';
        }

        return ($this->homeTeam->name ?? 'N/A').' vs '.($this->awayTeam->name ?? 'N/A');
    }
}
