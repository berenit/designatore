<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ogni 5 minuti: i solleciti (default ogni 8 ore) partono così al massimo con 5 minuti di ritardo
Schedule::command('designations:send-reminders')->everyFiveMinutes();

// Ogni notte: elimina i record scaduti dei modelli Prunable (indisponibilità arbitri già concluse)
Schedule::command('model:prune')->daily();
