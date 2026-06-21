<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Elenco dei ruoli previsti per la gara (es. Arbitro, giudici di linea,
     * 4°/5° uomo, osservatore, tutor, direttore di concentramento). null è
     * interpretato come ["Arbitro"] dal model per retrocompatibilità.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->json('required_roles')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('required_roles');
        });
    }
};
