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
            // Comitato regionale di competenza territoriale della gara.
            // Migration solo additiva: le gare già presenti in produzione
            // ricevono automaticamente il default 'Abruzzo'.
            $table->string('committee', 50)->default('Abruzzo')->after('competition_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('committee');
        });
    }
};
