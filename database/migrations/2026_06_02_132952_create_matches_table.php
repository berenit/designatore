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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time');
            $table->foreignId('venue_id')->constrained('venues');
            // Nome/titolo descrittivo per Concentramenti e Tornei (es. "Concentramento U14 - Roma").
            $table->string('name')->nullable();
            // Ruoli previsti per la gara; null è interpretato come ["Arbitro"] dal model per retrocompatibilità.
            $table->json('required_roles')->nullable();
            // Per gli eventi multi-squadra (Concentramenti/Tornei) non esistono casa/ospite: nullable.
            $table->foreignId('home_team_id')->nullable()->constrained('teams');
            $table->foreignId('away_team_id')->nullable()->constrained('teams');
            $table->enum('competition_type', [
                'Campionato', 'Coppa', 'Amichevole', 'Internazionale', 'Concentramento', 'Torneo',
            ])->default('Campionato');
            $table->enum('status', ['scheduled', 'postponed', 'cancelled', 'completed'])->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
