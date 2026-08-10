<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date_time',
    'venue_id',
    'name',
    'home_team_id',
    'away_team_id',
    'competition_type',
    'status',
    'required_roles',
])]
class RugbyMatch extends Model
{
    protected $table = 'matches';

    protected $casts = [
        'date_time' => 'datetime',
        'required_roles' => 'array',
    ];

    /** Tutti i tipi di competizione ammessi. */
    public const COMPETITION_TYPES = [
        'Campionato', 'Coppa', 'Amichevole', 'Internazionale', 'Concentramento', 'Torneo',
    ];

    /** Tipi che coinvolgono 3+ squadre (gestiti via pivot match_team). */
    public const MULTI_TEAM_TYPES = ['Concentramento', 'Torneo'];

    /**
     * Figure di gara extra selezionabili via checkbox alla creazione della gara.
     * Ogni chiave produce uno o più ruoli di designazione (i giudici di linea sono sempre due).
     */
    public const EXTRA_ROLE_OPTIONS = [
        'linesmen' => ['label' => 'Giudici di linea (2)', 'roles' => ['Assistente 1', 'Assistente 2']],
        'fourth' => ['label' => '4° uomo', 'roles' => ['4° uomo']],
        'fifth' => ['label' => '5° uomo', 'roles' => ['5° uomo']],
        'observer' => ['label' => 'Osservatore', 'roles' => ['Osservatore']],
        'tutor' => ['label' => 'Tutor', 'roles' => ['Tutor']],
        'director' => ['label' => 'Direttore di concentramento', 'roles' => ['Direttore di concentramento']],
    ];

    /** Ruolo sempre previsto su ogni gara. */
    public const DEFAULT_ROLE = 'Arbitro';

    /** Ruoli previsti per questa gara (sempre con l'Arbitro). */
    public function requiredRoles(): array
    {
        return $this->required_roles ?: [self::DEFAULT_ROLE];
    }

    /** Calcola l'elenco dei ruoli previsti a partire dalle chiavi checkbox selezionate. */
    public static function rolesFromExtraKeys(array $extraKeys): array
    {
        $roles = [self::DEFAULT_ROLE];

        foreach ($extraKeys as $key) {
            if (isset(self::EXTRA_ROLE_OPTIONS[$key])) {
                $roles = array_merge($roles, self::EXTRA_ROLE_OPTIONS[$key]['roles']);
            }
        }

        return array_values(array_unique($roles));
    }

    /** Reverse-map: chiavi checkbox attualmente selezionate per questa gara (per il form di modifica). */
    public function selectedExtraKeys(): array
    {
        $current = $this->requiredRoles();
        $keys = [];

        foreach (self::EXTRA_ROLE_OPTIONS as $key => $option) {
            // La chiave è selezionata se tutti i suoi ruoli sono presenti tra quelli previsti
            if (! array_diff($option['roles'], $current)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    // A match is played at a venue
    public function venue()
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

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

    /** Vero se ogni ruolo previsto ha una designazione attiva (non rifiutata/cancellata). */
    public function isFullyDesignated(): bool
    {
        $activeRoles = $this->designations
            ->where('status', '!=', 'cancelled')
            ->pluck('role');

        return collect($this->requiredRoles())->diff($activeRoles)->isEmpty();
    }

    /** Vero se esiste almeno una partita non ancora completamente designata. */
    public static function hasMatchesNeedingDesignation(): bool
    {
        return static::with('designations')->get()->contains(fn ($m) => ! $m->isFullyDesignated());
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

    /** Etichetta leggibile del campo di gioco per liste, report ed email. */
    public function getVenueLabelAttribute(): string
    {
        return $this->venue->label ?? '—';
    }
}
