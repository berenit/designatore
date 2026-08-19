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
        Schema::table('designations', function (Blueprint $table) {
            // Impedisce a livello DB la duplicazione della stessa designazione
            // (stesso arbitro, stesso ruolo, stessa gara), sia per le gare singole
            // (un solo ruolo per gara) sia per gli eventi multi-squadra (più arbitri
            // liberi con ruolo "Arbitro", ma mai lo stesso due volte).
            $table->unique(['match_id', 'role', 'referee_id'], 'designations_match_role_referee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropUnique('designations_match_role_referee_unique');
        });
    }
};
