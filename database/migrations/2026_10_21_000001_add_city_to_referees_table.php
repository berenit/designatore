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
            // Città di domicilio. Nullable perché gli arbitri già presenti
            // non ce l'hanno ancora: andrà compilata da UI.
            $table->string('city', 100)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
