<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorie precedenti all'introduzione di 'Derogato'.
     */
    private const PREVIOUS_LEVELS = [
        'Regionale',
        'Nazionale serie B',
        'Nazionale serie A',
        'Nazionale serie A Elite',
        'Assistente serie A',
        'Assistente serie A Elite',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            // Aggiunge la categoria 'Derogato' all'enum (nessun dato modificato).
            $table->enum('license_level', ['Derogato', ...self::PREVIOUS_LEVELS])
                ->default('Regionale')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->enum('license_level', self::PREVIOUS_LEVELS)
                ->default('Regionale')
                ->change();
        });
    }
};
