<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orari registrati dall'app (now()) quando il fuso era UTC, da riportare in ora italiana.
     * Esclusi matches.date_time (inserito a mano, già in ora italiana) e le colonne di sola data.
     */
    private const COLUMNS = [
        'designations' => ['assignment_date', 'reminder_sent_at', 'notified_at', 'escalated_at', 'created_at', 'updated_at'],
        'matches' => ['created_at', 'updated_at'],
        'match_team' => ['created_at', 'updated_at'],
        'referees' => ['created_at', 'updated_at'],
        'referee_unavailabilities' => ['created_at', 'updated_at'],
        'teams' => ['created_at', 'updated_at'],
        'venues' => ['created_at', 'updated_at'],
        'users' => ['email_verified_at', 'created_at', 'updated_at'],
        'failed_jobs' => ['failed_at'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convert('UTC', 'Europe/Rome');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->convert('Europe/Rome', 'UTC');
    }

    /** Converte ogni valore riga per riga, così il cambio ora legale/solare è rispettato. */
    private function convert(string $from, string $to): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(500, function ($rows) use ($table, $columns, $from, $to) {
                foreach ($rows as $row) {
                    $values = [];

                    foreach ($columns as $column) {
                        if ($row->{$column} !== null) {
                            $values[$column] = Carbon::parse($row->{$column}, $from)->setTimezone($to)->format('Y-m-d H:i:s');
                        }
                    }

                    if ($values !== []) {
                        DB::table($table)->where('id', $row->id)->update($values);
                    }
                }
            });
        }
    }
};
