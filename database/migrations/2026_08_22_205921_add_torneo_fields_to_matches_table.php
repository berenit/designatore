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
            // Squadre dei Tornei non presenti in anagrafica: memorizzate solo per quella gara.
            $table->json('extra_team_names')->nullable()->after('name');
            // Numero minimo di arbitri da designare (solo Tornei); null = 1 (comportamento di default).
            $table->unsignedTinyInteger('required_referees')->nullable()->after('required_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['extra_team_names', 'required_referees']);
        });
    }
};
