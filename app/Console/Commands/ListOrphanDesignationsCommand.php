<?php

namespace App\Console\Commands;

use App\Models\Designation;
use Illuminate\Console\Command;

class ListOrphanDesignationsCommand extends Command
{
    protected $signature = 'designations:list-orphans';

    protected $description = 'Elenca le designazioni su un ruolo non più previsto dalla partita, senza modificare nulla';

    public function handle(): int
    {
        // Le completate restano come storico anche se il ruolo non è più previsto
        $orphans = Designation::query()
            ->where('status', '!=', 'completed')
            ->with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'referee'])
            ->get()
            ->reject(fn ($designation) => in_array($designation->role, $designation->match->requiredRoles(), true))
            ->sortBy(fn ($designation) => $designation->match->date_time);

        if ($orphans->isEmpty()) {
            $this->info('Nessuna designazione orfana.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Partita', 'ID partita', 'Data', 'Ruolo', 'Arbitro', 'Stato', 'Ruoli previsti'],
            $orphans->map(fn ($designation) => [
                $designation->id,
                $designation->match->label,
                $designation->match_id,
                $designation->match->date_time->format('d/m/Y H:i'),
                $designation->role,
                $designation->referee->name,
                $designation->status_label,
                implode(', ', $designation->match->requiredRoles()),
            ])
        );

        $this->warn("Designazioni orfane: {$orphans->count()}. Quelle \"In attesa\" accettano ancora conferme e possono ricevere solleciti.");

        return self::SUCCESS;
    }
}
