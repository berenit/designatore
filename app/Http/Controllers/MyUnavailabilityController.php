<?php

namespace App\Http\Controllers;

use App\Mail\RefereeUnavailabilityConflictMail;
use App\Models\Referee;
use App\Models\RefereeUnavailability;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/** Periodi di indisponibilità gestiti dall'arbitro stesso, dalla voce "Indisponibilità" del menù. */
class MyUnavailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $referee = $this->referee($request);

        return view('profile.unavailabilities', [
            'referee' => $referee,
            'unavailabilities' => $referee->unavailabilities()->whereDate('end_date', '>=', today())->orderBy('start_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $referee = $this->referee($request);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
        ], [
            'start_date.required' => 'Indica la data di inizio.',
            'start_date.date' => 'La data di inizio non è valida.',
            'end_date.required' => 'Indica la data di fine.',
            'end_date.date' => 'La data di fine non è valida.',
            'end_date.after_or_equal' => 'La data di fine non può essere precedente alla data di inizio o a oggi.',
        ]);

        $period = $referee->unavailabilities()->create($validated);

        $conflicts = $this->conflictingDesignations($referee, $period);

        if ($conflicts->isEmpty()) {
            return Redirect::route('profile.unavailabilities.index')->with('success', 'Periodo di indisponibilità aggiunto.');
        }

        $this->notifyDesignators($referee, $period, $conflicts);

        return Redirect::route('profile.unavailabilities.index')->with(
            'error',
            'Periodo aggiunto, ma hai già delle designazioni in quelle date: restano valide e il designatore è stato avvisato.'
        );
    }

    public function destroy(Request $request, int $unavailability): RedirectResponse
    {
        // Cercando tra i periodi dell'arbitro, quelli degli altri risultano inesistenti (404)
        $this->referee($request)->unavailabilities()->findOrFail($unavailability)->delete();

        return Redirect::route('profile.unavailabilities.index')->with('success', 'Periodo di indisponibilità rimosso.');
    }

    private function referee(Request $request): Referee
    {
        $referee = $request->user()->referee;

        abort_unless($referee, 403);

        return $referee;
    }

    /** Designazioni ancora attive dell'arbitro su partite in programma nel periodo. */
    private function conflictingDesignations(Referee $referee, RefereeUnavailability $period): Collection
    {
        return $referee->designations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('match', fn ($query) => $query
                ->where('status', 'scheduled')
                ->whereDate('date_time', '>=', $period->start_date->toDateString())
                ->whereDate('date_time', '<=', $period->end_date->toDateString()))
            ->with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'assignedBy'])
            ->get();
    }

    /** Avvisa chi ha assegnato ogni designazione; se non è più noto, tutti i designatori. */
    private function notifyDesignators(Referee $referee, RefereeUnavailability $period, Collection $conflicts): void
    {
        $fallback = null;

        $recipients = [];

        foreach ($conflicts as $designation) {
            $emails = $designation->assignedBy
                ? [$designation->assignedBy->email]
                : ($fallback ??= User::where('role', 'designatore')->pluck('email')->all());

            foreach ($emails as $email) {
                $recipients[$email][] = $designation;
            }
        }

        foreach ($recipients as $email => $designations) {
            try {
                Mail::to($email)->send(new RefereeUnavailabilityConflictMail($referee, $period, collect($designations)));

                Log::info('Email di indisponibilità su gare designate inviata al designatore', [
                    'referee_id' => $referee->id,
                    'designatore_email' => $email,
                ]);
            } catch (\Throwable $e) {
                // Il periodo è già salvato: un problema di invio non deve far fallire l'operazione
                report($e);
            }
        }
    }
}
