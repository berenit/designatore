<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
        // Restituisce i valori così come salvati (servono per filtrare), ordinati per posizione in CATEGORY_ORDER
        return static::whereNotNull('league_division')
            ->distinct()
            ->orderBy('league_division')
            ->pluck('league_division')
            ->sortBy(fn ($category) => self::categoryRank($category))
            ->values();
    }

    /** Posizione della categoria in CATEGORY_ORDER, ignorando maiuscole e spazi superflui (non elencate = in coda). */
    public static function categoryRank(?string $category): int
    {
        $order = array_map(self::normalizeCategory(...), self::CATEGORY_ORDER);
        $index = array_search(self::normalizeCategory((string) $category), $order, true);

        return $index === false ? count($order) : $index;
    }

    private static function normalizeCategory(string $category): string
    {
        return mb_strtolower(trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $category)));
    }

    /** Ordina per categoria secondo CATEGORY_ORDER (le non elencate dopo, in ordine alfabetico). */
    public function scopeOrderByCategory(Builder $query, string $direction = 'asc'): Builder
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $cases = collect(self::CATEGORY_ORDER)->keys()->map(fn ($i) => "WHEN ? THEN {$i}")->implode(' ');
        $fallback = count(self::CATEGORY_ORDER);
        $bindings = array_map(self::normalizeCategory(...), self::CATEGORY_ORDER);

        return $query
            ->orderByRaw("CASE LOWER(TRIM(league_division)) {$cases} ELSE {$fallback} END {$direction}", $bindings)
            ->orderBy('league_division', $direction);
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
