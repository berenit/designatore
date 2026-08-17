<?php

namespace App\Http\Controllers;

use App\Mail\DesignationNotificationMail;
use App\Mail\DesignationRemovedMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $hasMatchesToDesignate = RugbyMatch::hasMatchesNeedingDesignation();

        return view('designations.index', compact('matches', 'weekStart', 'weekEnd', 'prevWeek', 'nextWeek', 'hasMatchesToDesignate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $preselect = $request->match_id;

        // Tutte le partite non ancora completamente designate, con i ruoli previsti
        // e le designazioni già esistenti (per il pre-fill). La partita preselezionata
        // (es. dall'icona "Azioni" di una partita già designata) resta inclusa anche se
        // già completa, per permettere di sostituire gli arbitri già assegnati.
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'teams', 'designations'])
            ->orderBy('date_time')
            ->get()
            ->filter(fn ($m) => ! $m->isFullyDesignated() || $m->id == $preselect)
            ->values();
        $referees = Referee::orderBy('name')->get();

        // Mappe per Alpine: ruoli previsti, se la gara è multi-squadra e arbitri già assegnati per ciascuna gara
        $matchRoles = $matches->mapWithKeys(fn ($m) => [$m->id => $m->requiredRoles()]);
        $matchIsMulti = $matches->mapWithKeys(fn ($m) => [$m->id => $m->isMultiTeam()]);
        $matchAssignments = $matches->mapWithKeys(fn ($m) => [
            $m->id => [
                // Per i ruoli singoli (tutti tranne l'Arbitro); l'Arbitro può ripetersi negli eventi multi-squadra
                'roles' => $m->designations->pluck('referee_id', 'role'),
                'arbitri' => $m->designations->where('role', RugbyMatch::DEFAULT_ROLE)->pluck('referee_id')->values(),
            ],
        ]);

        // Data di ciascuna gara (per il confronto "stessa giornata" lato client)
        $matchDates = $matches->mapWithKeys(fn ($m) => [$m->id => $m->date_time->format('Y-m-d')]);

        // Prenotazioni attive per arbitro (data + partita), per evidenziare le sovrapposizioni nella stessa giornata
        $refereeBookings = DB::table('designations')
            ->join('matches', 'matches.id', '=', 'designations.match_id')
            ->where('designations.status', '!=', 'cancelled')
            ->selectRaw('designations.referee_id, designations.match_id, DATE(matches.date_time) as match_date')
            ->get()
            ->groupBy('referee_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['date' => $r->match_date, 'match_id' => $r->match_id])->values());

        return view('designations.create', compact(
            'matches', 'referees', 'preselect', 'matchRoles', 'matchIsMulti', 'matchAssignments', 'matchDates', 'refereeBookings'
        ));
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
            'referees' => ['nullable', 'array'],
            'referees.*' => ['nullable', 'exists:referees,id'],
            'arbitri' => ['nullable', 'array'],
            'arbitri.*' => ['nullable', 'exists:referees,id'],
            'notes' => 'nullable|string|max:1000',
        ]);

        $match = RugbyMatch::findOrFail($validated['match_id']);

        // Arbitri: negli eventi multi-squadra (Concentramento/Torneo) sono liberi e senza ruolo
        // specifico (uno o più); nelle gare singole resta un solo Arbitro, come prima.
        if ($match->isMultiTeam()) {
            $arbitriIds = collect($validated['arbitri'] ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        } else {
            $single = $validated['referees'][RugbyMatch::DEFAULT_ROLE] ?? null;
            $arbitriIds = $single ? collect([(int) $single]) : collect();
        }

        // L'Arbitro è sempre obbligatorio (almeno uno)
        if ($arbitriIds->isEmpty()) {
            return Redirect::back()->withInput()->withErrors([
                'arbitri' => "È obbligatorio assegnare almeno l'Arbitro.",
            ]);
        }

        // Tieni gli altri ruoli previsti dalla gara (tutti tranne l'Arbitro, gestito sopra)
        // con un arbitro effettivamente selezionato
        $otherAssignments = collect($validated['referees'] ?? [])
            ->except(RugbyMatch::DEFAULT_ROLE)
            ->only($match->requiredRoles())
            ->filter(fn ($refId) => ! empty($refId))
            ->map(fn ($refId) => (int) $refId);

        // Nei Concentramenti (non nei Tornei) il Direttore di concentramento è sempre obbligatorio
        if ($match->competition_type === 'Concentramento' && ! $otherAssignments->has('Direttore di concentramento')) {
            return Redirect::back()->withInput()->withErrors([
                'referees' => 'Nei Concentramenti è obbligatorio assegnare un Direttore di concentramento.',
            ]);
        }

        // Nessun arbitro può ricoprire due ruoli/posizioni nella stessa gara
        // (considera anche le designazioni esistenti sugli altri ruoli non toccati da questo invio)
        $untouchedOthers = $match->designations()
            ->where('role', '!=', RugbyMatch::DEFAULT_ROLE)
            ->whereNotIn('role', $otherAssignments->keys()->all())
            ->pluck('referee_id', 'role');

        $allIds = $arbitriIds->values()->merge($otherAssignments->values())->merge($untouchedOthers->values());

        if ($allIds->duplicates()->isNotEmpty()) {
            return Redirect::back()->withInput()->withErrors([
                'referees' => 'Lo stesso arbitro non può ricoprire due ruoli nella stessa gara.',
            ]);
        }

        // Creazione ed email atomiche: se un invio fallisce, viene annullato tutto (rollback)
        try {
            $designations = DB::transaction(function () use ($arbitriIds, $otherAssignments, $match, $validated) {
                $created = [];

                if ($match->isMultiTeam()) {
                    // Uno o più arbitri liberi: sincronizza rimuovendo chi non è più selezionato
                    $match->designations()
                        ->where('role', RugbyMatch::DEFAULT_ROLE)
                        ->whereNotIn('referee_id', $arbitriIds->all())
                        ->delete();

                    foreach ($arbitriIds as $refereeId) {
                        $created[] = $this->saveDesignation(
                            $match,
                            RugbyMatch::DEFAULT_ROLE,
                            $refereeId,
                            $validated,
                            ['match_id' => $match->id, 'role' => RugbyMatch::DEFAULT_ROLE, 'referee_id' => $refereeId]
                        );
                    }
                } else {
                    // Gara singola: un solo Arbitro, come prima
                    $created[] = $this->saveDesignation(
                        $match,
                        RugbyMatch::DEFAULT_ROLE,
                        $arbitriIds->first(),
                        $validated,
                        ['match_id' => $match->id, 'role' => RugbyMatch::DEFAULT_ROLE]
                    );
                }

                foreach ($otherAssignments as $role => $refereeId) {
                    $created[] = $this->saveDesignation(
                        $match,
                        $role,
                        $refereeId,
                        $validated,
                        ['match_id' => $match->id, 'role' => $role]
                    );
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

    /** Crea/aggiorna una designazione e invia l'email di notifica al relativo arbitro. */
    private function saveDesignation(RugbyMatch $match, string $role, int $refereeId, array $validated, array $key): Designation
    {
        $designation = Designation::updateOrCreate($key, [
            'referee_id' => $refereeId,
            'assigned_by' => auth()->id(),
            'assignment_date' => now(),
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        $designation->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee']);

        Mail::to($designation->referee->email)
            ->send(new DesignationNotificationMail($designation));

        Log::info('Email di designazione inviata all\'arbitro', [
            'designation_id' => $designation->id,
            'match_id' => $designation->match_id,
            'referee_email' => $designation->referee->email,
        ]);

        return $designation;
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
        // Ruoli previsti dalla gara, garantendo che quello attuale resti selezionabile, nell'ordine canonico
        $roles = collect([...$designation->match->requiredRoles(), $designation->role])
            ->unique()
            ->sortBy(function ($role) {
                $index = array_search($role, Designation::ROLES, true);

                return $index === false ? count(Designation::ROLES) : $index;
            })
            ->values()
            ->all();

        // Arbitri già impegnati nella stessa giornata su un'ALTRA gara, da evidenziare in rosso
        // (esclude la gara corrente: gli altri ruoli sulla stessa gara non sono un conflitto)
        $matchDate = $designation->match->date_time->format('Y-m-d');
        $conflictingRefereeIds = DB::table('designations')
            ->join('matches', 'matches.id', '=', 'designations.match_id')
            ->where('designations.status', '!=', 'cancelled')
            ->where('designations.match_id', '!=', $designation->match_id)
            ->whereDate('matches.date_time', $matchDate)
            ->pluck('designations.referee_id')
            ->unique();

        return view('designations.edit', compact('designation', 'matches', 'referees', 'roles', 'conflictingRefereeIds'));
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
        $designation->load(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee']);

        // Se l'arbitro ha già rifiutato, è già informato: evita di notificarlo di nuovo.
        if ($designation->status !== 'cancelled') {
            Mail::to($designation->referee->email)->send(new DesignationRemovedMail($designation));

            Log::info('Email di rimozione designazione inviata all\'arbitro', [
                'designation_id' => $designation->id,
                'match_id' => $designation->match_id,
                'referee_email' => $designation->referee->email,
            ]);
        }

        $designation->delete();

        return Redirect::route('designations.index')
            ->with('success', 'Designazione eliminata.');
    }
}
