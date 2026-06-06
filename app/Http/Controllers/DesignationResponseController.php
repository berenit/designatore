<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationResponseController extends Controller
{
    public function respond(Request $request, Designation $designation, string $action)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link non valido o scaduto.');
        }

        if (! in_array($action, ['confirm', 'decline'])) {
            abort(400);
        }

        if (in_array($designation->status, ['confirmed', 'cancelled', 'completed'])) {
            return view('designations.respond', [
                'designation' => $designation,
                'action' => $action,
                'alreadyProcessed' => true,
            ]);
        }

        $designation->update([
            'status' => $action === 'confirm' ? 'confirmed' : 'cancelled',
        ]);

        return view('designations.respond', [
            'designation' => $designation->fresh(['match.homeTeam', 'match.awayTeam', 'match.teams', 'referee']),
            'action' => $action,
            'alreadyProcessed' => false,
        ]);
    }
}
