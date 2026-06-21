<?php

namespace App\Http\Controllers;

use App\Models\RugbyMatch;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class RugbyMatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams'])->orderBy('date_time')->get();

        return view('rugby_matches.index', compact('matches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teams = Team::orderBy('name')->get();
        $leagues = $teams->pluck('league_division')->unique()->sort()->values();

        $nextSunday = now()->next(Carbon::SUNDAY);
        $kickoffHour = $nextSunday->isDST() ? 14 : 15;
        $defaultDate = $nextSunday->setTime($kickoffHour, 30)->format('Y-m-d\TH:i');

        // Mappa team_id => ['2026-06-08', ...] con tutte le date già occupate
        $bookedDates = $this->bookedTeamDates();
        $competitionTypes = RugbyMatch::COMPETITION_TYPES;
        $multiTeamTypes = RugbyMatch::MULTI_TEAM_TYPES;
        $extraRoleOptions = RugbyMatch::EXTRA_ROLE_OPTIONS;

        return view('rugby_matches.create', compact(
            'teams', 'leagues', 'defaultDate', 'bookedDates', 'competitionTypes', 'multiTeamTypes', 'extraRoleOptions'
        ));
    }

    /** Valida i dati della partita applicando regole diverse per gli eventi multi-squadra. */
    private function validateMatch(Request $request): array
    {
        $isMultiTeam = in_array($request->competition_type, RugbyMatch::MULTI_TEAM_TYPES, true);

        $rules = [
            'date_time' => 'required|date',
            'venue' => 'required|string|max:255',
            'competition_type' => ['required', Rule::in(RugbyMatch::COMPETITION_TYPES)],
            'status' => ['required', Rule::in(['scheduled', 'postponed', 'cancelled', 'completed'])],
            'extra_roles' => 'nullable|array',
            'extra_roles.*' => [Rule::in(array_keys(RugbyMatch::EXTRA_ROLE_OPTIONS))],
        ];

        if ($isMultiTeam) {
            $rules['name'] = 'required|string|max:255';
            $rules['team_ids'] = 'required|array|min:3';
            $rules['team_ids.*'] = ['distinct', 'exists:teams,id'];
        } else {
            $rules['home_team_id'] = ['required', 'exists:teams,id'];
            $rules['away_team_id'] = ['required', 'exists:teams,id', 'different:home_team_id'];
        }

        return $request->validate($rules);
    }

    /** Vero se la squadra ha già un impegno (partita o evento) nella data indicata. */
    private function conflictForTeam(int $teamId, string $date, ?int $excludeMatchId = null): bool
    {
        $single = RugbyMatch::where(function ($q) use ($teamId) {
            $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
        })
            ->whereDate('date_time', $date)
            ->when($excludeMatchId, fn ($q) => $q->where('id', '!=', $excludeMatchId))
            ->exists();

        if ($single) {
            return true;
        }

        return DB::table('match_team')
            ->join('matches', 'matches.id', '=', 'match_team.match_id')
            ->where('match_team.team_id', $teamId)
            ->whereDate('matches.date_time', $date)
            ->when($excludeMatchId, fn ($q) => $q->where('matches.id', '!=', $excludeMatchId))
            ->exists();
    }

    /**
     * Controlla i conflitti di data per le squadre coinvolte.
     *
     * @param  array<int, array{id:int, field:string}>  $teams  squadre da verificare con la chiave-errore associata
     * @return array|null array di errori o null
     */
    private function checkTeamConflicts(array $teams, string $dateTime, ?int $excludeMatchId = null): ?array
    {
        $date = Carbon::parse($dateTime)->toDateString();

        // Raggruppa i nomi in conflitto per chiave-errore: negli eventi multi-squadra
        // più squadre condividono il campo 'team_ids' e vanno riportate tutte insieme.
        $conflicting = [];

        foreach ($teams as $team) {
            if ($this->conflictForTeam($team['id'], $date, $excludeMatchId)) {
                $conflicting[$team['field']][] = Team::find($team['id'])?->name ?? 'La squadra';
            }
        }

        if (! $conflicting) {
            return null;
        }

        $errors = [];

        foreach ($conflicting as $field => $names) {
            $errors[$field] = count($names) === 1
                ? "{$names[0]} ha già un impegno in questa data."
                : implode(', ', $names).' hanno già un impegno in questa data.';
        }

        return $errors;
    }

    /** Costruisce l'elenco di squadre da verificare a partire dai dati validati. */
    private function teamsToCheck(array $validated): array
    {
        if (in_array($validated['competition_type'], RugbyMatch::MULTI_TEAM_TYPES, true)) {
            return array_map(fn ($id) => ['id' => (int) $id, 'field' => 'team_ids'], $validated['team_ids']);
        }

        return [
            ['id' => (int) $validated['home_team_id'], 'field' => 'home_team_id'],
            ['id' => (int) $validated['away_team_id'], 'field' => 'away_team_id'],
        ];
    }

    /** Restituisce team_id => [date, ...] per tutte le partite ed eventi esistenti. */
    private function bookedTeamDates(?int $excludeMatchId = null): array
    {
        $home = RugbyMatch::selectRaw('home_team_id as team_id, DATE(date_time) as match_date')
            ->whereNotNull('home_team_id')
            ->when($excludeMatchId, fn ($q) => $q->where('id', '!=', $excludeMatchId));

        $away = RugbyMatch::selectRaw('away_team_id as team_id, DATE(date_time) as match_date')
            ->whereNotNull('away_team_id')
            ->when($excludeMatchId, fn ($q) => $q->where('id', '!=', $excludeMatchId));

        $pivot = DB::table('match_team')
            ->join('matches', 'matches.id', '=', 'match_team.match_id')
            ->selectRaw('match_team.team_id as team_id, DATE(matches.date_time) as match_date')
            ->when($excludeMatchId, fn ($q) => $q->where('matches.id', '!=', $excludeMatchId));

        return $home->union($away)->union($pivot)->get()
            ->groupBy('team_id')
            ->map(fn ($rows) => $rows->pluck('match_date')->unique()->values())
            ->toArray();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateMatch($request);

        $errors = $this->checkTeamConflicts(
            $this->teamsToCheck($validated),
            $validated['date_time']
        );

        if ($errors) {
            return Redirect::back()->withInput()->withErrors($errors);
        }

        $match = RugbyMatch::create($this->matchAttributes($validated));

        if ($match->isMultiTeam()) {
            $match->teams()->sync($validated['team_ids']);
        }

        return Redirect::route('rugby-matches.index')
            ->with('success', 'Partita programmata con successo.');
    }

    /** Estrae gli attributi da persistire azzerando i campi non pertinenti al tipo di evento. */
    private function matchAttributes(array $validated): array
    {
        $isMultiTeam = in_array($validated['competition_type'], RugbyMatch::MULTI_TEAM_TYPES, true);

        return [
            'date_time' => $validated['date_time'],
            'venue' => $validated['venue'],
            'competition_type' => $validated['competition_type'],
            'status' => $validated['status'],
            'name' => $isMultiTeam ? $validated['name'] : null,
            'home_team_id' => $isMultiTeam ? null : $validated['home_team_id'],
            'away_team_id' => $isMultiTeam ? null : $validated['away_team_id'],
            'required_roles' => RugbyMatch::rolesFromExtraKeys($validated['extra_roles'] ?? []),
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(RugbyMatch $rugbyMatch)
    {
        $rugbyMatch->load(['homeTeam', 'awayTeam', 'teams', 'designations.referee']);
        $match = $rugbyMatch;

        return view('rugby_matches.show', compact('match'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RugbyMatch $rugbyMatch)
    {
        $teams = Team::orderBy('name')->get();
        $leagues = $teams->pluck('league_division')->unique()->sort()->values();

        $rugbyMatch->load(['homeTeam', 'teams']);
        $match = $rugbyMatch;

        $bookedDates = $this->bookedTeamDates($rugbyMatch->id);
        $competitionTypes = RugbyMatch::COMPETITION_TYPES;
        $multiTeamTypes = RugbyMatch::MULTI_TEAM_TYPES;
        $extraRoleOptions = RugbyMatch::EXTRA_ROLE_OPTIONS;
        $selectedExtraKeys = $rugbyMatch->selectedExtraKeys();
        $selectedTeamIds = $rugbyMatch->teams->pluck('id')->map(fn ($id) => (string) $id)->values();

        // Campionato corrente per preselezionare il filtro delle squadre
        $currentLeague = $rugbyMatch->homeTeam->league_division
            ?? optional($rugbyMatch->teams->first())->league_division
            ?? '';

        return view('rugby_matches.edit', compact(
            'match', 'teams', 'leagues', 'bookedDates', 'competitionTypes', 'multiTeamTypes',
            'extraRoleOptions', 'selectedExtraKeys', 'selectedTeamIds', 'currentLeague'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RugbyMatch $rugbyMatch)
    {
        $validated = $this->validateMatch($request);

        $errors = $this->checkTeamConflicts(
            $this->teamsToCheck($validated),
            $validated['date_time'],
            $rugbyMatch->id
        );

        if ($errors) {
            return Redirect::back()->withInput()->withErrors($errors);
        }

        $rugbyMatch->update($this->matchAttributes($validated));

        if ($rugbyMatch->isMultiTeam()) {
            $rugbyMatch->teams()->sync($validated['team_ids']);
        } else {
            $rugbyMatch->teams()->detach();
        }

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
