<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ruoli comuni alle due versioni dell'enum (senza Osservatore/Valutatore). */
    private const OTHER_ROLES = [
        'Arbitro', 'Assistente 1', 'Assistente 2', '4° uomo', '5° uomo', 'Tutor', 'Direttore di concentramento',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->rename('Osservatore', 'Valutatore');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rename('Valutatore', 'Osservatore');
    }

    /** Rinomina il ruolo nell'enum delle designazioni e nei ruoli previsti delle gare. */
    private function rename(string $from, string $to): void
    {
        // Enum temporaneo con entrambi i valori, così l'aggiornamento delle righe è valido.
        $this->setRoleEnum([...self::OTHER_ROLES, $from, $to]);

        DB::table('designations')->where('role', $from)->update(['role' => $to]);

        DB::table('matches')->whereNotNull('required_roles')->orderBy('id')->chunkById(500, function ($matches) use ($from, $to) {
            foreach ($matches as $match) {
                $roles = json_decode($match->required_roles, true);

                if (is_array($roles) && in_array($from, $roles, true)) {
                    $roles = array_map(fn ($role) => $role === $from ? $to : $role, $roles);
                    DB::table('matches')->where('id', $match->id)->update(['required_roles' => json_encode($roles)]);
                }
            }
        });

        $this->setRoleEnum([...self::OTHER_ROLES, $to]);
    }

    private function setRoleEnum(array $roles): void
    {
        Schema::table('designations', function (Blueprint $table) use ($roles) {
            $table->enum('role', $roles)->default('Arbitro')->change();
        });
    }
};
