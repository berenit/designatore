<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorie precedenti all'introduzione di 'Fuori Ruolo'.
     */
    private const PREVIOUS_LEVELS = [
        'Derogato',
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
            // Aggiunge la categoria 'Fuori Ruolo' all'enum (nessun dato modificato).
            $table->enum('license_level', [...self::PREVIOUS_LEVELS, 'Fuori Ruolo'])
                ->default('Regionale')
                ->change();
        });

        Schema::table('referees', function (Blueprint $table) {
            // Abilitato al ruolo di Valutatore (unico ruolo designabile per i Fuori Ruolo).
            $table->boolean('is_evaluator')->default(false)->after('license_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->dropColumn('is_evaluator');
        });

        Schema::table('referees', function (Blueprint $table) {
            $table->enum('license_level', self::PREVIOUS_LEVELS)
                ->default('Regionale')
                ->change();
        });
    }
};
