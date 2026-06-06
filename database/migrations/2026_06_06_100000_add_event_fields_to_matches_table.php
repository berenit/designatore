<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Nome/titolo descrittivo per Concentramenti e Tornei (es. "Concentramento U14 - Roma")
            $table->string('name')->nullable()->after('venue');

            // Per gli eventi multi-squadra non esistono casa/ospite: rendiamo i campi nullable
            $table->foreignId('home_team_id')->nullable()->change();
            $table->foreignId('away_team_id')->nullable()->change();

            // Estendiamo i tipi di competizione con Concentramento e Torneo
            $table->enum('competition_type', [
                'League', 'Cup', 'Friendly', 'International', 'Tournament', 'Concentramento', 'Torneo',
            ])->default('League')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('name');

            $table->enum('competition_type', [
                'League', 'Cup', 'Friendly', 'International', 'Tournament',
            ])->default('League')->change();

            $table->foreignId('home_team_id')->nullable(false)->change();
            $table->foreignId('away_team_id')->nullable(false)->change();
        });
    }
};
