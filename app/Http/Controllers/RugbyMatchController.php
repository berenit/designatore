<?php

namespace App\Http\Controllers;

use App\Models\RugbyMatch;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class RugbyMatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam'])->orderBy('date_time')->get();
        return view('rugby_matches.index', compact('matches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teams   = Team::orderBy('name')->get();
        $leagues = $teams->pluck('league_division')->unique()->sort()->values();

        $nextSunday  = now()->next(\Carbon\Carbon::SUNDAY);
        $kickoffHour = $nextSunday->isDST() ? 14 : 15;
        $defaultDate = $nextSunday->setTime($kickoffHour, 30)->format('Y-m-d\TH:i');

        // Mappa team_id => ['2026-06-08', ...] con tutte le date già occupate
        $bookedDates = $this->bookedTeamDates();

        return view('rugby_matches.create', compact('teams', 'leagues', 'defaultDate', 'bookedDates'));
    }

    /** Controlla conflitti di data per le due squadre. Ritorna array di errori o null. */
    private function checkTeamConflicts(int $homeId, int $awayId, string $dateTime, int $excludeMatchId = null): ?array
    {
        $date = \Carbon\Carbon::parse($dateTime)->toDateString();

        $conflict = fn(int $teamId) => RugbyMatch::where(function ($q) use ($teamId) {
            $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
        })
            ->whereDate('date_time', $date)
            ->when($excludeMatchId, fn($q) => $q->where('id', '!=', $excludeMatchId))
            ->exists();

        $errors = [];
        if ($conflict($homeId)) {
            $errors['home_team_id'] = 'La squadra di casa ha già una partita in questa data.';
        }
        if ($conflict($awayId)) {
            $errors['away_team_id'] = 'La squadra ospite ha già una partita in questa data.';
        }

        return $errors ?: null;
    }

    /** Restituisce team_id => [date, ...] per tutte le partite esistenti. */
    private function bookedTeamDates(int $excludeMatchId = null): array
    {
        $query = RugbyMatch::selectRaw('home_team_id as team_id, DATE(date_time) as match_date')
            ->union(
                RugbyMatch::selectRaw('away_team_id as team_id, DATE(date_time) as match_date')
            );

        if ($excludeMatchId) {
            // Per l'edit escludiamo la partita corrente dal controllo
            $query = RugbyMatch::selectRaw('home_team_id as team_id, DATE(date_time) as match_date')
                ->where('id', '!=', $excludeMatchId)
                ->union(
                    RugbyMatch::selectRaw('away_team_id as team_id, DATE(date_time) as match_date')
                        ->where('id', '!=', $excludeMatchId)
                );
        }

        return $query->get()
            ->groupBy('team_id')
            ->map(fn($rows) => $rows->pluck('match_date')->unique()->values())
            ->toArray();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_time'        => 'required|date',
            'venue'            => 'required|string|max:255',
            'home_team_id'     => ['required', 'exists:teams,id'],
            'away_team_id'     => ['required', 'exists:teams,id', 'different:home_team_id'],
            'competition_type' => ['required', Rule::in(['League', 'Cup', 'Friendly', 'International', 'Tournament'])],
            'status'           => ['required', Rule::in(['scheduled', 'postponed', 'cancelled', 'completed'])],
        ]);

        $errors = $this->checkTeamConflicts(
            $validated['home_team_id'],
            $validated['away_team_id'],
            $validated['date_time']
        );

        if ($errors) {
            return Redirect::back()->withInput()->withErrors($errors);
        }

        RugbyMatch::create($validated);

        return Redirect::route('rugby-matches.index')
            ->with('success', 'Partita programmata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RugbyMatch $rugbyMatch)
    {
        $rugbyMatch->load(['homeTeam', 'awayTeam', 'designation.referee']);
        $match = $rugbyMatch;
        return view('rugby_matches.show', compact('match'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RugbyMatch $rugbyMatch)
    {
        $teams = Team::orderBy('name')->get();
        $match = $rugbyMatch;
        return view('rugby_matches.edit', compact('match', 'teams'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RugbyMatch $rugbyMatch)
    {
        $validated = $request->validate([
            'date_time' => 'required|date',
            'venue' => 'required|string|max:255',
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id', 'different:home_team_id'],
            'competition_type' => ['required', Rule::in(['League', 'Cup', 'Friendly', 'International', 'Tournament'])],
            'status' => ['required', Rule::in(['scheduled', 'postponed', 'cancelled', 'completed'])],
        ]);

        $errors = $this->checkTeamConflicts(
            $validated['home_team_id'],
            $validated['away_team_id'],
            $validated['date_time'],
            $rugbyMatch->id
        );

        if ($errors) {
            return Redirect::back()->withInput()->withErrors($errors);
        }

        $rugbyMatch->update($validated);

        return Redirect::route('rugby-matches.index')
            ->with('success', 'Partita aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RugbyMatch $rugbyMatch)
    {
        $rugbyMatch->delete();

        return Redirect::route('rugby-matches.index')
            ->with('success', 'Partita eliminata.');
    }
}
