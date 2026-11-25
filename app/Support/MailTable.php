<?php

namespace App\Support;

class MailTable
{
    /**
     * Rende un valore sicuro come cella di una tabella Markdown nelle mail:
     * un a capo o un "|" spezzerebbero la riga o sposterebbero le colonne.
     */
    public static function cell(?string $value): string
    {
        $value = preg_replace('/\s*\R\s*/u', ' ', trim((string) $value));

        return str_replace('|', '\|', $value);
    }
}
