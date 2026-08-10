<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
use App\Models\Team;

class DashboardController extends Controller
{
    public function public()
    {
        $upcomingMatches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'venue', 'designations.referee'])
            ->where('status', 'scheduled')
            ->where('date_time', '>=', now())
            ->orderBy('date_time')
            ->limit(20)
            ->get();

        return view('dashboard.public', compact('upcomingMatches'));
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

        return view('dashboard.private', compact('stats', 'recentDesignations', 'upcomingMatches', 'hasMatchesToDesignate'));
    }
}
