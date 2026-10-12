<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Quando è stata inviata davvero l'ultima richiesta di conferma (null = nessuna email partita)
            $table->timestamp('notified_at')->nullable()->after('reminder_sent_at');
            // Solleciti già inviati per la richiesta di conferma corrente
            $table->unsignedTinyInteger('reminders_sent')->default(0)->after('notified_at');
            // Quando il designatore è stato avvisato della mancata risposta
            $table->timestamp('escalated_at')->nullable()->after('reminders_sent');
        });

        // Designazioni già in attesa: la richiesta è partita alla creazione. Per non far partire
        // in blocco al deploy solleciti arretrati e avvisi al designatore, si considerano già
        // inviati i solleciti scaduti, e già avvisate quelle oltre la scadenza.
        $interval = config('designations.reminder_interval_minutes') * 60;
        $deadline = config('designations.response_deadline_minutes') * 60;
        $maxReminders = max(0, (int) ceil($deadline / $interval) - 1);

        DB::table('designations')->where('status', 'pending')->orderBy('id')->each(function ($designation) use ($interval, $deadline, $maxReminders) {
            $notifiedAt = now()->parse($designation->created_at);
            $elapsed = $notifiedAt->diffInSeconds(now());

            DB::table('designations')->where('id', $designation->id)->update([
                'notified_at' => $notifiedAt,
                'reminders_sent' => min($maxReminders, (int) floor($elapsed / $interval)),
                'escalated_at' => $elapsed >= $deadline ? now() : null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'reminders_sent', 'escalated_at']);
        });
    }
};
