<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function public(Request $request)
    {
        $category = $request->query('category');

        // Solo le partite della settimana corrente (lunedì–domenica)
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // Ai visitatori non autenticati il nome dell'arbitro è mostrato solo dal giovedì in poi
        $showRefereeNames = $request->user() !== null
            || now()->greaterThanOrEqualTo($weekStart->copy()->next(Carbon::THURSDAY));

        $upcomingMatches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'venue', 'designations.referee'])
            ->where('status', 'scheduled')
            ->whereDate('date_time', '>=', $weekStart->toDateString())
            ->whereDate('date_time', '<=', $weekEnd->toDateString())
            ->when($category, fn ($query, $category) => $query->inCategory($category))
            ->orderBy('date_time')
            ->get();

        $categories = Team::orderedCategories();

        return view('dashboard.public', compact('upcomingMatches', 'categories', 'category', 'weekStart', 'weekEnd', 'showRefereeNames'));
    }

    public function private(Request $request)
    {
        $stats = [
            'referees' => Referee::count(),
            'available_referees' => Referee::where('availability_status', 'available')->count(),
            'teams' => Team::count(),
            // Solo la settimana corrente (lunedì–domenica), escluse le partite già giocate
            'upcoming_matches' => RugbyMatch::where('status', 'scheduled')->inWeek(now())->where('date_time', '>=', now())->count(),
            // Designazioni delle partite della settimana corrente (lunedì–domenica)
            'pending_designations' => Designation::where('status', 'pending')->whereHas('match', fn ($q) => $q->inWeek(now()))->count(),
            'confirmed_designations' => Designation::where('status', 'confirmed')->whereHas('match', fn ($q) => $q->inWeek(now()))->count(),
            // Solo la settimana corrente (lunedì–domenica), escluse le partite già giocate
            'matches_without_designation' => RugbyMatch::where('status', 'scheduled')
                ->inWeek(now())
                ->where('date_time', '>=', now())
                ->whereDoesntHave('designations')
                ->count(),
        ];

        $recentDesignations = Designation::with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee'])
            ->whereHas('match', fn ($q) => $q->inWeek(now()))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $upcomingMatches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'venue', 'designations.referee'])
            ->where('status', 'scheduled')
            ->where('date_time', '>=', now())
            ->orderBy('date_time')
            ->limit(5)
            ->get();

        $hasMatchesToDesignate = RugbyMatch::hasMatchesNeedingDesignation();

        // Stagione sportiva: da luglio dell'anno in corso (o precedente, se siamo tra gennaio e giugno) a giugno successivo
        $seasonStartYear = now()->month >= 7 ? now()->year : now()->year - 1;
        $seasonStart = Carbon::create($seasonStartYear, 7, 1)->startOfDay();
        $seasonEnd = Carbon::create($seasonStartYear + 1, 6, 30)->endOfDay();
        $seasonLabel = $seasonStartYear.'/'.($seasonStartYear + 1);

        // Ruoli da considerare nelle statistiche di stagione (nessuna selezione valida = solo Arbitro)
        $seasonRoles = array_values(array_intersect(Designation::ROLES, (array) $request->input('season_roles', [])))
            ?: [RugbyMatch::DEFAULT_ROLE];

        // Partite distinte per cui ciascun arbitro è stato designato nella stagione con uno dei ruoli scelti
        // (whereDate: confronto per sola data, più affidabile in SQLite di un confronto diretto su date_time)
        $seasonMatchesByReferee = DB::table('designations')
            ->join('matches', 'matches.id', '=', 'designations.match_id')
            ->where('designations.status', '!=', 'cancelled')
            ->whereIn('designations.role', $seasonRoles)
            ->whereDate('matches.date_time', '>=', $seasonStart->toDateString())
            ->whereDate('matches.date_time', '<=', $seasonEnd->toDateString())
            ->select('designations.referee_id', 'designations.match_id')
            ->get()
            ->groupBy('referee_id')
            ->map(fn ($rows) => $rows->pluck('match_id')->unique()->count());

        $refereeSeasonCounts = Referee::orderBy('name')->get()
            ->map(fn ($referee) => [
                'referee' => $referee,
                'count' => $seasonMatchesByReferee->get($referee->id, 0),
            ])
            ->sortByDesc('count')
            ->values();

        return view('dashboard.private', compact(
            'stats', 'recentDesignations', 'upcomingMatches', 'hasMatchesToDesignate', 'refereeSeasonCounts', 'seasonLabel', 'seasonRoles'
        ));
    }
}
