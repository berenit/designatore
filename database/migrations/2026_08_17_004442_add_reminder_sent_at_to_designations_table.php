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
        Schema::table('designations', function (Blueprint $table) {
            // Data/ora dell'ultimo sollecito inviato all'arbitro (null se non
            // ancora sollecitato). Distinta da created_at perché una
            // designazione pending può ricevere più solleciti nel tempo.
            $table->timestamp('reminder_sent_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
