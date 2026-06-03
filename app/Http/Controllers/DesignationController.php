<?php

namespace App\Http\Controllers;

use App\Mail\DesignationNotificationMail;
use App\Models\Designation;
use App\Models\Referee;
use App\Models\RugbyMatch;
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
            ? \Carbon\Carbon::parse($request->week)
            : now()
        )->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();

        $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->endOfDay();

        // Tutte le partite della settimana (qualunque stato), con designazione se esiste
        // whereDate confronta solo la parte data, più affidabile in SQLite
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam', 'designation.referee'])
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
        $matches  = RugbyMatch::with(['homeTeam', 'awayTeam'])
            ->whereDoesntHave('designation')
            ->orderBy('date_time')
            ->get();
        $referees  = Referee::orderBy('name')->get();
        $preselect = $request->match_id;
        return view('designations.create', compact('matches', 'referees', 'preselect'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'match_id'   => ['required', 'exists:matches,id'],
            'referee_id' => ['required', 'exists:referees,id'],
            'notes'      => 'nullable|string|max:1000',
        ]);

        $existing = Designation::where('match_id', $validated['match_id'])
            ->where('referee_id', $validated['referee_id'])
            ->exists();

        if ($existing) {
            return Redirect::back()
                ->withInput()
                ->withErrors(['referee_id' => 'Questo arbitro è già assegnato a questa partita.']);
        }

        $designation = Designation::create([
            ...$validated,
            'assigned_by'     => auth()->id(),
            'assignment_date' => now(),
            'status'          => 'pending',
        ]);

        $designation->load(['match.homeTeam', 'match.awayTeam', 'referee']);

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
        return view('designations.show', compact('designation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Designation $designation)
    {
        $matches = RugbyMatch::with(['homeTeam', 'awayTeam'])->get();
        $referees = Referee::orderBy('name')->get();
        return view('designations.edit', compact('designation', 'matches', 'referees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'match_id'   => ['required', 'exists:matches,id'],
            'referee_id' => ['required', 'exists:referees,id'],
            'status'     => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'notes'      => 'nullable|string|max:1000',
        ]);

        $existing = Designation::where('match_id', $validated['match_id'])
            ->where('referee_id', $validated['referee_id'])
            ->where('id', '!=', $designation->id)
            ->exists();

        if ($existing) {
            return Redirect::back()
                ->withInput()
                ->withErrors(['referee_id' => 'Questo arbitro è già assegnato a questa partita.']);
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
