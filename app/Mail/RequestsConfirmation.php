<?php

namespace App\Mail;

use App\Models\Designation;

/**
 * Email che chiede all'arbitro di confermare/rifiutare una designazione: quando parte davvero
 * avvia il ciclo dei solleciti (vedi SendDesignationRemindersCommand).
 */
interface RequestsConfirmation
{
    public function confirmationDesignation(): Designation;
}
