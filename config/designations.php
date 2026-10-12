<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Solleciti per le designazioni senza risposta
    |--------------------------------------------------------------------------
    |
    | Dopo l'email di designazione (o di partita modificata), se l'arbitro non
    | risponde gli viene rimandata l'email ogni "reminder_interval_minutes"
    | ("SOLLECITO 1:", "SOLLECITO 2:", ... e infine "ULTIMO SOLLECITO:").
    | Allo scadere di "response_deadline_minutes" senza risposta viene avvisato
    | il designatore. Con i valori di default: solleciti a 8, 16, 24, 32 e 40
    | ore, avviso al designatore a 48 ore. I valori possono essere frazionari.
    |
    */

    'reminder_interval_minutes' => (float) env('DESIGNATION_REMINDER_INTERVAL_MINUTES', 8 * 60),

    'response_deadline_minutes' => (float) env('DESIGNATION_RESPONSE_DEADLINE_MINUTES', 48 * 60),

];
