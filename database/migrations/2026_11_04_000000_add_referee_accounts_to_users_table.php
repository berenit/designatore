<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PREVIOUS_ROLES = ['designatore', 'delegato'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [...self::PREVIOUS_ROLES, 'arbitro'])
                ->default('designatore')
                ->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referee_id')->nullable()->unique()->constrained('referees')->nullOnDelete();
            // Vero finché l'arbitro usa ancora il numero di tessera come password.
            $table->boolean('must_change_password')->default(false);
            // Nuova email in attesa di conferma tramite il link inviato al nuovo indirizzo.
            $table->string('pending_email')->nullable();
        });

        $this->createRefereeAccounts();
    }

    /**
     * Crea l'account degli arbitri con tessera. Chi ha già un utente con la stessa email
     * (es. un designatore che è anche arbitro) viene collegato e mantiene ruolo e password.
     */
    private function createRefereeAccounts(): void
    {
        $users = DB::table('users')->get(['id', 'email'])->keyBy(fn ($user) => Str::lower($user->email));

        foreach (DB::table('referees')->get() as $referee) {
            $existing = $users->get(Str::lower($referee->email));

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update(['referee_id' => $referee->id]);
            } elseif (filled($referee->license_number)) {
                DB::table('users')->insert([
                    'name' => $referee->name,
                    'email' => $referee->email,
                    'role' => 'arbitro',
                    'referee_id' => $referee->id,
                    'password' => Hash::make($referee->license_number),
                    'must_change_password' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'arbitro')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referee_id']);
            $table->dropUnique(['referee_id']);
            $table->dropColumn(['referee_id', 'must_change_password', 'pending_email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', self::PREVIOUS_ROLES)
                ->default('designatore')
                ->change();
        });
    }
};
