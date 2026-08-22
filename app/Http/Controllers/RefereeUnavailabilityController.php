<?php

namespace App\Http\Controllers;

use App\Models\Referee;
use App\Models\RefereeUnavailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RefereeUnavailabilityController extends Controller
{
    /**
     * Store a new unavailability period for the given referee.
     */
    public function store(Request $request, Referee $referee)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $referee->unavailabilities()->create($validated);

        return Redirect::route('referees.edit', $referee)
            ->with('success', 'Periodo di indisponibilità aggiunto.');
    }

    /**
     * Remove an unavailability period.
     */
    public function destroy(Referee $referee, RefereeUnavailability $unavailability)
    {
        abort_unless($unavailability->referee_id === $referee->id, 404);

        $unavailability->delete();

        return Redirect::route('referees.edit', $referee)
            ->with('success', 'Periodo di indisponibilità rimosso.');
    }
}
