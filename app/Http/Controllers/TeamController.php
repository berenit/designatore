<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sort = in_array($request->sort, ['name', 'league_division']) ? $request->sort : 'name';
        $dir = $request->dir === 'desc' ? 'desc' : 'asc';
        $league = $request->filter_league;

        $leagues = Team::distinct()->orderBy('league_division')->pluck('league_division');

        $teams = Team::when($league, fn ($q) => $q->where('league_division', $league))
            ->orderBy($sort, $dir)
            ->get();

        return view('teams.index', compact('teams', 'leagues', 'sort', 'dir', 'league'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('teams.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'league_division' => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        Team::create($validated);

        return Redirect::route('teams.index')
            ->with('success', 'Squadra creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        return view('teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        return view('teams.edit', compact('team'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'league_division' => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        $team->update($validated);

        return Redirect::route('teams.index')
            ->with('success', 'Squadra aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->delete();

        return Redirect::route('teams.index')
            ->with('success', 'Squadra eliminata.');
    }
}
