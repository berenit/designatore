<?php

namespace App\Console\Commands;

use App\Mail\DesignationNotificationMail;
use App\Models\Designation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDesignationRemindersCommand extends Command
{
    protected $signature = 'designations:send-reminders';

    protected $description = "Reinvia l'email di designazione agli arbitri che non hanno ancora risposto entro 24 ore dall'ultimo invio";

    public function handle(): int
    {
        $threshold = now()->subHours(24);

        $designations = Designation::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($threshold) {
                $query->whereNull('reminder_sent_at')
                    ->where('created_at', '<=', $threshold);
            })
            ->orWhere(function ($query) use ($threshold) {
                $query->where('status', 'pending')
                    ->where('reminder_sent_at', '<=', $threshold);
            })
            ->with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee'])
            ->get();

        foreach ($designations as $designation) {
            Mail::to($designation->referee->email)->send(new DesignationNotificationMail($designation));

            $designation->update(['reminder_sent_at' => now()]);

            Log::info('Sollecito email di designazione inviato all\'arbitro', [
                'designation_id' => $designation->id,
                'match_id' => $designation->match_id,
                'referee_email' => $designation->referee->email,
            ]);
        }

        $this->info("Solleciti inviati: {$designations->count()}.");

        return self::SUCCESS;
    }
}
