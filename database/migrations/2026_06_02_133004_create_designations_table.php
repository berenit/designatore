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
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('referees')->onDelete('cascade');
            // Una gara può avere più arbitri: un solo arbitro per ruolo nelle gare singole,
            // più "Arbitro" nei Concentramenti e nei Tornei.
            $table->enum('role', [
                'Arbitro', 'Assistente 1', 'Assistente 2', 'Osservatore', '4° uomo', '5° uomo', 'Tutor', 'Direttore di concentramento',
            ])->default('Arbitro');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('assignment_date');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
