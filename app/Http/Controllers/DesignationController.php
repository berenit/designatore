<?php

namespace App\Http\Controllers;

use App\Mail\DesignationNotificationMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Settimana di riferimento: lunedì–domenica (default = settimana corrente)
        $weekStart = ($request->week
            ? Carbon::parse($request->week)
            : now()
        )->startOfWeek(Carbon::MONDAY)->startOfDay();

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // Tutte le partite della settimana (qualunque stato), con le designazioni se esistono
        // whereDate confronta solo la parte data, più affidabile in SQLite
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'designations.referee'])
            ->whereDate('date_time', '>=', $weekStart->toDateString())
            ->whereDate('date_time', '<=', $weekEnd->toDateString())
            ->orderBy('date_time')
            ->get();

        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');

        return view('designations.index', compact('matches', 'weekStart', 'weekEnd', 'prevWeek', 'nextWeek'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Tutte le partite: più arbitri possono essere designati sullo stesso incontro
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams'])
            ->orderBy('date_time')
            ->get();
        $referees = Referee::orderBy('name')->get();
        $roles = Designation::ROLES;
        $preselect = $request->match_id;

        return view('designations.create', compact('matches', 'referees', 'roles', 'preselect'));
    }

    /** Verifica i vincoli di unicità arbitro/ruolo su una partita. Ritorna gli errori o null. */
    private function checkDesignationConflicts(int $matchId, int $refereeId, string $role, ?int $excludeId = null): ?array
    {
        $sameReferee = Designation::where('match_id', $matchId)
            ->where('referee_id', $refereeId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($sameReferee) {
            return ['referee_id' => 'Questo arbitro è già designato per questo incontro.'];
        }

        // Nelle gare singole un solo arbitro per ruolo; negli eventi multi-squadra il ruolo può ripetersi
        $match = RugbyMatch::find($matchId);
        if ($match && ! $match->isMultiTeam()) {
            $roleTaken = Designation::where('match_id', $matchId)
                ->where('role', $role)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if ($roleTaken) {
                return ['role' => "Il ruolo «{$role}» è già assegnato per questo incontro."];
            }
        }

        return null;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'match_id' => ['required', 'exists:matches,id'],
            'referee_id' => ['required', 'exists:referees,id'],
            'role' => ['required', Rule::in(Designation::ROLES)],
            'notes' => 'nullable|string|max:1000',
        ]);

        $errors = $this->checkDesignationConflicts(
            $validated['match_id'],
            $validated['referee_id'],
            $validated['role']
        );

        if ($errors) {
            return Redirect::back()->withInput()->withErrors($errors);
        }

        $designation = Designation::create([
            ...$validated,
            'assigned_by' => auth()->id(),
            'assignment_date' => now(),
            'status' => 'pending',
        ]);

        $designation->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'referee']);

        Mail::to($designation->referee->email)
            ->send(new DesignationNotificationMail($designation));

        return Redirect::route('designations.index')
            ->with('success', "Arbitro {$designation->referee->name} designato con successo. Email di notifica inviata.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Designation $designation)
    {
        $designation->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'referee']);

        return view('designations.show', compact('designation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Designation $designation)
    {
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams'])->orderBy('date_time')->get();
        $referees = Referee::orderBy('name')->get();
        $roles = Designation::ROLES;

        return view('designations.edit', compact('designation', 'matches', 'referees', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'match_id' => ['required', 'exists:matches,id'],
            'referee_id' => ['required', 'exists:referees,id'],
            'role' => ['required', Rule::in(Designation::ROLES)],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'notes' => 'nullable|string|max:1000',
        ]);

        $errors = $this->checkDesignationConflicts(
            $validated['match_id'],
            $validated['referee_id'],
            $validated['role'],
            $designation->id
        );

        if ($errors) {
            return Redirect::back()->withInput()->withErrors($errors);
        }

        $designation->update($validated);

        return Redirect::route('designations.index')
            ->with('success', 'Designazione aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Designation $designation)
    {
        $designation->delete();

        return Redirect::route('designations.index')
            ->with('success', 'Designazione eliminata.');
    }
}
