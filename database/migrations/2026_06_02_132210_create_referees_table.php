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
        Schema::create('referees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('license_level', [
                'Regionale',
                'Nazionale serie B',
                'Nazionale serie A',
                'Nazionale serie A Elite',
                'Assistente serie A',
                'Assistente serie A Elite',
            ])->default('Regionale');
            $table->enum('availability_status', ['available', 'unavailable', 'limited'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referees');
    }
};
