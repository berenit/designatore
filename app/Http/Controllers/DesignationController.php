<?php

namespace App\Http\Controllers;

use App\Mail\DesignationNotificationMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'venue', 'designations.referee'])
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
        // Tutte le partite, con i ruoli previsti e le designazioni già esistenti (per il pre-fill)
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'designations'])
            ->orderBy('date_time')
            ->get();
        $referees = Referee::orderBy('name')->get();
        $preselect = $request->match_id;

        // Mappe per Alpine: ruoli previsti e arbitri già assegnati per ciascuna gara
        $matchRoles = $matches->mapWithKeys(fn ($m) => [$m->id => $m->requiredRoles()]);
        $matchAssignments = $matches->mapWithKeys(fn ($m) => [
            $m->id => $m->designations->pluck('referee_id', 'role'),
        ]);

        return view('designations.create', compact('matches', 'referees', 'preselect', 'matchRoles', 'matchAssignments'));
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
            'referees' => ['required', 'array'],
            'referees.*' => ['nullable', 'exists:referees,id'],
            'notes' => 'nullable|string|max:1000',
        ]);

        $match = RugbyMatch::findOrFail($validated['match_id']);

        // Tieni solo i ruoli previsti dalla gara con un arbitro effettivamente selezionato
        $assignments = collect($validated['referees'])
            ->only($match->requiredRoles())
            ->filter(fn ($refId) => ! empty($refId))
            ->map(fn ($refId) => (int) $refId);

        // L'Arbitro è sempre obbligatorio
        if (! $assignments->has(RugbyMatch::DEFAULT_ROLE)) {
            return Redirect::back()->withInput()->withErrors([
                'referees' => "È obbligatorio assegnare almeno l'Arbitro.",
            ]);
        }

        // Nessun arbitro può ricoprire due ruoli nella stessa gara
        // (considera anche le designazioni esistenti sui ruoli non toccati da questo invio)
        $untouched = $match->designations()
            ->whereNotIn('role', $assignments->keys()->all())
            ->pluck('referee_id', 'role');

        if ($untouched->merge($assignments)->duplicates()->isNotEmpty()) {
            return Redirect::back()->withInput()->withErrors([
                'referees' => 'Lo stesso arbitro non può ricoprire due ruoli nella stessa gara.',
            ]);
        }

        // Creazione ed email atomiche: se un invio fallisce, viene annullato tutto (rollback)
        try {
            $designations = DB::transaction(function () use ($assignments, $match, $validated) {
                $created = [];

                foreach ($assignments as $role => $refereeId) {
                    $designation = Designation::updateOrCreate(
                        ['match_id' => $match->id, 'role' => $role],
                        [
                            'referee_id' => $refereeId,
                            'assigned_by' => auth()->id(),
                            'assignment_date' => now(),
                            'status' => 'pending',
                            'notes' => $validated['notes'] ?? null,
                        ]
                    );

                    $designation->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee']);

                    Mail::to($designation->referee->email)
                        ->send(new DesignationNotificationMail($designation));

                    $created[] = $designation;
                }

                return $created;
            });
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()->withInput()->withErrors([
                'email' => 'Impossibile inviare le email di notifica: le designazioni non sono state salvate. Riprova.',
            ]);
        }

        return Redirect::route('designations.index')
            ->with('success', count($designations).' designazione/i salvata/e con successo. Email di notifica inviate.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Designation $designation)
    {
        $designation->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee']);

        return view('designations.show', compact('designation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Designation $designation)
    {
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams'])->orderBy('date_time')->get();
        $referees = Referee::orderBy('name')->get();
        // Ruoli previsti dalla gara, garantendo che quello attuale resti selezionabile
        $roles = array_values(array_unique([...$designation->match->requiredRoles(), $designation->role]));

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
