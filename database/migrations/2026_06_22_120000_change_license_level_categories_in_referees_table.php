<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le categorie arbitrali passano dai vecchi livelli patente (Local,
     * Regional, National, International) alle categorie italiane previste:
     * Regionale, Nazionale serie B, Nazionale serie A, Nazionale serie A Elite,
     * Assistente serie A, Assistente serie A Elite.
     */
    public function up(): void
    {
        // Rimuove il vincolo enum trasformando temporaneamente in stringa.
        Schema::table('referees', function (Blueprint $table) {
            $table->string('license_level')->default('Regionale')->change();
        });

        // Mappa i vecchi valori sulle nuove categorie.
        DB::table('referees')->where('license_level', 'International')->update(['license_level' => 'Nazionale serie A Elite']);
        DB::table('referees')->where('license_level', 'National')->update(['license_level' => 'Nazionale serie A']);
        DB::table('referees')->where('license_level', 'Regional')->update(['license_level' => 'Regionale']);
        DB::table('referees')->where('license_level', 'Local')->update(['license_level' => 'Regionale']);

        // Riapplica il vincolo enum con le nuove categorie.
        Schema::table('referees', function (Blueprint $table) {
            $table->enum('license_level', [
                'Regionale',
                'Nazionale serie B',
                'Nazionale serie A',
                'Nazionale serie A Elite',
                'Assistente serie A',
                'Assistente serie A Elite',
            ])->default('Regionale')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->string('license_level')->default('Local')->change();
        });

        DB::table('referees')->where('license_level', 'Nazionale serie A Elite')->update(['license_level' => 'International']);
        DB::table('referees')->whereIn('license_level', ['Nazionale serie A', 'Nazionale serie B'])->update(['license_level' => 'National']);
        DB::table('referees')->whereIn('license_level', ['Regionale', 'Assistente serie A', 'Assistente serie A Elite'])->update(['license_level' => 'Regional']);

        Schema::table('referees', function (Blueprint $table) {
            $table->enum('license_level', ['National', 'Regional', 'International', 'Local'])->default('Local')->change();
        });
    }
};
