<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
    /** Ordine di visualizzazione delle categorie; quelle non elencate seguono in ordine alfabetico. */
    public const CATEGORY_ORDER = ['Serie A', 'Serie B', 'Serie C', 'U18 Elite', 'U18', 'U16', 'U14'];

    /** Categorie (league_division) distinte presenti in anagrafica, nell'ordine di CATEGORY_ORDER. */
    public static function orderedCategories(): Collection
    {
        $categories = static::whereNotNull('league_division')
            ->distinct()
            ->orderBy('league_division')
            ->pluck('league_division');

        $known = collect(self::CATEGORY_ORDER)->intersect($categories);

        return $known->merge($categories->diff($known))->values();
    }

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
