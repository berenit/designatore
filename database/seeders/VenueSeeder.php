<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Campi da rugby dell'Abruzzo.
     */
    public function run(): void
    {
        // [ nome, città, indirizzo ]
        $venues = [
            ['Angelo Tromberra', 'Avezzano', 'Via dei Gladioli'],
            ['Centi Colella', "L'Aquila", 'S.S. 17 Ovest - Loc. Centi Coella'],
            ["Piazza d'Armi", "L'Aquila", 'Via Pietro Paolo Mennea'],
        ];

        foreach ($venues as [$name, $city, $address]) {
            Venue::create([
                'name' => $name,
                'city' => $city,
                'address' => $address,
                'contact_person' => null,
                'contact_phone' => null,
            ]);
        }
    }
}
