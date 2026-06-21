<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Aggiunge il ruolo "Direttore di concentramento" all'enum delle designazioni.
     */
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->enum('role', [
                'Arbitro', 'Assistente 1', 'Assistente 2', 'Osservatore', '4° uomo', '5° uomo', 'Tutor', 'Direttore di concentramento',
            ])->default('Arbitro')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->enum('role', [
                'Arbitro', 'Assistente 1', 'Assistente 2', 'Osservatore', '4° uomo', '5° uomo', 'Tutor',
            ])->default('Arbitro')->change();
        });
    }
};
