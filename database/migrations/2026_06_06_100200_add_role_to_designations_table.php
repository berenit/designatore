<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ogni designazione porta ora un ruolo: una gara può avere più arbitri
     * (un solo arbitro per ruolo nelle gare singole, più "Arbitro" nei
     * Concentramenti e nei Tornei).
     */
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->enum('role', [
                'Arbitro', 'Assistente 1', 'Assistente 2', 'Osservatore', '4° uomo', '5° uomo', 'Tutor',
            ])->default('Arbitro')->after('referee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
