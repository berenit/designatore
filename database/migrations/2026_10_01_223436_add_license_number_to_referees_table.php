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
        Schema::table('referees', function (Blueprint $table) {
            // Numero di tessera (1-10 cifre). Stringa e non intero per non
            // perdere eventuali zeri iniziali e perché 10 cifre superano il
            // range di un INT. Nullable perché gli arbitri già presenti in
            // produzione non hanno ancora un numero: andrà compilato da UI.
            // L'unique ammette più NULL (MySQL e SQLite).
            $table->string('license_number', 10)->nullable()->unique()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->dropUnique(['license_number']);
            $table->dropColumn('license_number');
        });
    }
};
