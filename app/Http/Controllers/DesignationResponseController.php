<?php

namespace App\Http\Controllers;

use App\Mail\DesignationDeclinedMail;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // Link di un arbitro poi sostituito. Quelli senza "referee" sono stati inviati prima
        // che il link riportasse il destinatario: restano validi per non invalidare le email già partite.
        if ($request->has('referee') && (int) $request->query('referee') !== (int) $designation->referee_id) {
            return response()->view('designations.respond-invalid', [], 410);
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

        $designation = $designation->fresh(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee', 'assignedBy']);

        if ($action === 'decline' && $designation->assignedBy) {
            Mail::to($designation->assignedBy->email)->send(new DesignationDeclinedMail($designation));

            Log::info('Email di designazione rifiutata inviata al designatore', [
                'designation_id' => $designation->id,
                'match_id' => $designation->match_id,
                'referee_email' => $designation->referee->email,
                'designatore_email' => $designation->assignedBy->email,
            ]);
        }

        return view('designations.respond', [
            'designation' => $designation,
            'action' => $action,
            'alreadyProcessed' => false,
        ]);
    }
}
