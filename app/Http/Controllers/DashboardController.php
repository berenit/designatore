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

        $upcomingMatches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'venue', 'designations.referee'])
            ->where('status', 'scheduled')
            ->where('date_time', '>=', now())
            ->when($category, function ($query, $category) {
                $query->where(function ($q) use ($category) {
                    $q->whereHas('homeTeam', fn ($t) => $t->where('league_division', $category))
                        ->orWhereHas('awayTeam', fn ($t) => $t->where('league_division', $category))
                        ->orWhereHas('teams', fn ($t) => $t->where('league_division', $category));
                });
            })
            ->orderBy('date_time')
            ->limit(20)
            ->get();

        $categories = Team::whereNotNull('league_division')
            ->distinct()
            ->orderBy('league_division')
            ->pluck('league_division');

        return view('dashboard.public', compact('upcomingMatches', 'categories', 'category'));
    }

    public function private()
    {
        $stats = [
            'referees' => Referee::count(),
            'available_referees' => Referee::where('availability_status', 'available')->count(),
            'teams' => Team::count(),
            'upcoming_matches' => RugbyMatch::where('status', 'scheduled')->where('date_time', '>=', now())->count(),
            'pending_designations' => Designation::where('status', 'pending')->count(),
            'confirmed_designations' => Designation::where('status', 'confirmed')->count(),
            'matches_without_designation' => RugbyMatch::where('status', 'scheduled')
                ->where('date_time', '>=', now())
                ->whereDoesntHave('designations')
                ->count(),
        ];

        $recentDesignations = Designation::with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee'])
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

        // Partite distinte per cui ciascun arbitro è stato designato nella stagione (whereDate: confronto
        // per sola data, più affidabile in SQLite di un confronto diretto su date_time)
        $seasonMatchesByReferee = DB::table('designations')
            ->join('matches', 'matches.id', '=', 'designations.match_id')
            ->where('designations.status', '!=', 'cancelled')
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
            'stats', 'recentDesignations', 'upcomingMatches', 'hasMatchesToDesignate', 'refereeSeasonCounts', 'seasonLabel'
        ));
    }
}
