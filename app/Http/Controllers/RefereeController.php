<?php

namespace App\Http\Controllers;

use App\Models\Referee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class RefereeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $referees = Referee::orderBy('name')->get();

        return view('referees.index', compact('referees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('referees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:referees',
            'phone' => 'nullable|string|max:20',
            'license_level' => ['required', Rule::in(Referee::CATEGORIES)],
            'availability_status' => ['required', Rule::in(array_keys(Referee::AVAILABILITY_LABELS))],
        ]);

        Referee::create($validated);

        return Redirect::route('referees.index')
            ->with('success', 'Arbitro creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Referee $referee)
    {
        return view('referees.show', compact('referee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Referee $referee)
    {
        return view('referees.edit', compact('referee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Referee $referee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('referees')->ignore($referee->id)],
            'phone' => 'nullable|string|max:20',
            'license_level' => ['required', Rule::in(Referee::CATEGORIES)],
            'availability_status' => ['required', Rule::in(array_keys(Referee::AVAILABILITY_LABELS))],
        ]);

        $referee->update($validated);

        return Redirect::route('referees.index')
            ->with('success', 'Arbitro aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Referee $referee)
    {
        $referee->delete();

        return Redirect::route('referees.index')
            ->with('success', 'Arbitro eliminato.');
    }
}
