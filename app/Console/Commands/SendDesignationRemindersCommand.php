<?php

namespace App\Console\Commands;

use App\Mail\DesignationNotificationMail;
use App\Mail\DesignationUnansweredMail;
use App\Models\Designation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDesignationRemindersCommand extends Command
{
    protected $signature = 'designations:send-reminders';

    protected $description = 'Sollecita gli arbitri che non hanno risposto alla richiesta di conferma (default: ogni 8 ore) e avvisa il designatore allo scadere del tempo (default: 48 ore)';

    public function handle(): int
    {
        $interval = Designation::reminderIntervalSeconds();
        $deadline = Designation::responseDeadlineSeconds();
        $maxReminders = Designation::maxReminders();

        // Solo le richieste di conferma partite davvero (notified_at) e non ancora segnalate al designatore
        $designations = Designation::query()
            ->where('status', 'pending')
            ->whereNotNull('notified_at')
            ->whereNull('escalated_at')
            ->with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee', 'assignedBy'])
            ->get();

        $reminded = 0;
        $escalated = 0;

        foreach ($designations as $designation) {
            // Un invio fallito non blocca gli altri: la designazione resta da gestire e si ritenta al giro successivo
            try {
                $elapsed = $designation->notified_at->diffInSeconds(now());

                if ($elapsed >= $deadline) {
                    $this->escalate($designation);
                    $escalated++;

                    continue;
                }

                // Sollecito dovuto in base al tempo trascorso (8h → 1, 16h → 2, ...). Se lo scheduler è
                // rimasto fermo e ne sono scaduti più d'uno, parte solo il più recente, non tutti insieme.
                $due = min($maxReminders, (int) floor($elapsed / $interval));

                if ($due > $designation->reminders_sent) {
                    $this->remind($designation, $due);
                    $reminded++;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Solleciti inviati: {$reminded}. Designatori avvisati: {$escalated}.");

        return self::SUCCESS;
    }

    private function remind(Designation $designation, int $number): void
    {
        $label = Designation::reminderLabel($number);

        Mail::to($designation->referee->email)->send(new DesignationNotificationMail($designation, $label));

        $designation->update(['reminders_sent' => $number, 'reminder_sent_at' => now()]);

        Log::info('Sollecito email di designazione inviato all\'arbitro', [
            'designation_id' => $designation->id,
            'match_id' => $designation->match_id,
            'referee_email' => $designation->referee->email,
            'reminder' => $label,
        ]);
    }

    private function escalate(Designation $designation): void
    {
        if (! $designation->assignedBy) {
            // Segnata comunque come gestita: non c'è nessuno a cui scrivere, e non va ritentata a ogni esecuzione
            $designation->update(['escalated_at' => now()]);

            Log::warning('Designazione senza risposta ma senza designatore da avvisare', [
                'designation_id' => $designation->id,
            ]);

            return;
        }

        Mail::to($designation->assignedBy->email)->send(new DesignationUnansweredMail($designation));

        $designation->update(['escalated_at' => now()]);

        Log::info('Email di designazione senza risposta inviata al designatore', [
            'designation_id' => $designation->id,
            'match_id' => $designation->match_id,
            'designatore_email' => $designation->assignedBy->email,
        ]);
    }
}
