<?php

namespace Database\Seeders;

use App\Models\Referee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RefereeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Arbitri di rugby dell'Abruzzo con la rispettiva categoria.
     *
     * Nomi e categorie sono ricavati dall'Organigramma Arbitrale ufficiale
     * della Commissione Nazionale Arbitri FIR, stagione 2025/2026
     * (https://federugby.it/wp-content/uploads/Organigramma-CNA-2025-2026_DEF.pdf),
     * selezionando gli ufficiali di gara con sezione/città abruzzese.
     *
     * Le categorie sono mappate sui valori previsti da Referee::CATEGORIES.
     * Le email e i telefoni NON sono dati pubblici: sono segnaposto generati
     * dal nome (le email servono solo a soddisfare il vincolo di unicità).
     */
    public function run(): void
    {
        // [ nome, città, categoria, ruolo/nota dalla fonte ]
        $referees = [
            ['Daniele Pompa', 'Chieti', 'Nazionale serie A Elite', 'Arbitro Serie A Elite'],
            ['Luca Giurina', 'L\'Aquila', 'Assistente serie A Elite', 'Assistente Serie A Elite'],
            ['Gianluca Capone', 'Pescara', 'Assistente serie A', 'Assistente Serie A'],
            ['Danilo Antonio Paolini', 'Pescara', 'Assistente serie A', 'Assistente Serie A'],
            ['Fabio Paolucci', 'L\'Aquila', 'Nazionale serie B', 'Arbitro Nazionale Serie B'],
            ['Riccardo Persio', 'L\'Aquila', 'Nazionale serie B', 'Arbitro Nazionale Serie B'],
            ['Richard Spagnoli', 'L\'Aquila', 'Nazionale serie B', 'Arbitro Nazionale Serie B'],
            ['Alessio Silvano Colamarino', 'Sulmona', 'Regionale', 'Capo Sezione Sulmona'],
        ];

        foreach ($referees as [$name, $city, $category, $role]) {
            $slug = Str::slug($name, '.');

            Referee::create([
                'name' => $name,
                'email' => $slug.'@arbitri-abruzzo.example',
                'phone' => null,
                'license_level' => $category,
                'availability_status' => 'available',
            ]);
        }
    }
}
