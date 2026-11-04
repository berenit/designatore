<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Il numero di tessera compare nei report: non può essere usato come nuova password. */
class NotLicenseNumber implements ValidationRule
{
    public function __construct(private ?User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $licenseNumber = $this->user?->referee?->license_number;

        if (filled($licenseNumber) && $value === $licenseNumber) {
            $fail('La nuova password non può essere il numero di tessera.');
        }
    }
}
