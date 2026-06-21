<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traduce i tipi di competizione in italiano. 'Tournament' (gara singola)
     * viene rimosso e le gare esistenti vengono convertite in 'Amichevole';
     * 'Concentramento' e 'Torneo' erano già in italiano.
     */
    public function up(): void
    {
        // Rimuove temporaneamente il vincolo enum.
        Schema::table('matches', function (Blueprint $table) {
            $table->string('competition_type')->default('Campionato')->change();
        });

        DB::table('matches')->where('competition_type', 'League')->update(['competition_type' => 'Campionato']);
        DB::table('matches')->where('competition_type', 'Cup')->update(['competition_type' => 'Coppa']);
        DB::table('matches')->where('competition_type', 'Friendly')->update(['competition_type' => 'Amichevole']);
        DB::table('matches')->where('competition_type', 'International')->update(['competition_type' => 'Internazionale']);
        DB::table('matches')->where('competition_type', 'Tournament')->update(['competition_type' => 'Amichevole']);

        // Riapplica il vincolo enum con i tipi tradotti.
        Schema::table('matches', function (Blueprint $table) {
            $table->enum('competition_type', [
                'Campionato', 'Coppa', 'Amichevole', 'Internazionale', 'Concentramento', 'Torneo',
            ])->default('Campionato')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('competition_type')->default('League')->change();
        });

        DB::table('matches')->where('competition_type', 'Campionato')->update(['competition_type' => 'League']);
        DB::table('matches')->where('competition_type', 'Coppa')->update(['competition_type' => 'Cup']);
        DB::table('matches')->where('competition_type', 'Amichevole')->update(['competition_type' => 'Friendly']);
        DB::table('matches')->where('competition_type', 'Internazionale')->update(['competition_type' => 'International']);

        Schema::table('matches', function (Blueprint $table) {
            $table->enum('competition_type', [
                'League', 'Cup', 'Friendly', 'International', 'Tournament', 'Concentramento', 'Torneo',
            ])->default('League')->change();
        });
    }
};
