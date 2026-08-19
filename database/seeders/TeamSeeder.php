<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carica le società di rugby dell'Abruzzo (nomi e città reali), creando
     * una squadra per ogni campionato in cui il club è iscritto.
     *
     * Campionati gestiti: Serie C, U14, U16, U18, U18 Elite.
     *
     * Nota: i recapiti (referente, email, telefono) sono dati di esempio
     * generati a partire dal nome del club, non contatti reali.
     */
    public function run(): void
    {
        // [ nome club, città, [campionati a cui è iscritto] ]
        $clubs = [
            ['L\'Aquila Rugby', 'L\'Aquila', ['Serie C', 'U14', 'U16', 'U18', 'U18 Elite']],
            ['Polisportiva Paganica Rugby', 'L\'Aquila', ['Serie C', 'U14', 'U16', 'U18', 'U18 Elite']],
            ['Gran Sasso Rugby', 'L\'Aquila', ['Serie C', 'U14', 'U16', 'U18', 'U18 Elite']],
            ['Rugby Experience L\'Aquila', 'L\'Aquila', ['Serie C', 'U14', 'U16', 'U18']],
            ['Isweb Avezzano Rugby', 'Avezzano', ['Serie C', 'U14', 'U16', 'U18', 'U18 Elite']],
            ['Rugby Sulmona', 'Sulmona', ['Serie C', 'U14', 'U16', 'U18']],
            ['Pescara Rugby', 'Pescara', ['Serie C', 'U14', 'U16', 'U18', 'U18 Elite']],
            ['Polisportiva Chieti Rugby', 'Chieti', ['Serie C', 'U14', 'U16', 'U18']],
            ['Rugby Lanciano', 'Lanciano', ['Serie C', 'U14', 'U16', 'U18']],
            ['Polisportiva Vasto Rugby', 'Vasto', ['Serie C', 'U14', 'U16', 'U18']],
            ['Teramo Rugby', 'Teramo', ['Serie C', 'U14', 'U16', 'U18']],
            ['Rugby Giulianova', 'Giulianova', ['Serie C', 'U14', 'U16', 'U18']],
            ['Rugby Roseto', 'Roseto degli Abruzzi', ['U14', 'U16', 'U18']],
        ];

        foreach ($clubs as [$name, $city, $divisions]) {
            $slug = Str::slug($name);

            foreach ($divisions as $division) {
                Team::create([
                    'name' => $name,
                    'city' => $city,
                    'league_division' => $division,
                    'contact_person' => 'Segreteria '.$name,
                    'contact_email' => 'info@'.$slug.'.it',
                    'contact_phone' => null,
                ]);
            }
        }
    }
}
