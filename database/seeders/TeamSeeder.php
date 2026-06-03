<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::create([
            'name' => 'All Blacks',
            'city' => 'Auckland',
            'league_division' => 'International',
            'contact_person' => 'John Doe',
            'contact_email' => 'contact@allblacks.nz',
            'contact_phone' => '+6491234567',
        ]);

        Team::create([
            'name' => 'Springboks',
            'city' => 'Johannesburg',
            'league_division' => 'International',
            'contact_person' => 'Pieter van der Berg',
            'contact_email' => 'info@springboks.za',
            'contact_phone' => '+27115551234',
        ]);

        Team::create([
            'name' => 'Wallabies',
            'city' => 'Sydney',
            'league_division' => 'International',
            'contact_person' => 'Michael Clarke',
            'contact_email' => 'admin@wallabies.com.au',
            'contact_phone' => '+61298765432',
        ]);

        Team::create([
            'name' => 'Local Rugby Club',
            'city' => 'Local Town',
            'league_division' => 'Premier League',
            'contact_person' => 'Local Coach',
            'contact_email' => 'coach@localrugby.com',
            'contact_phone' => '+15551234567',
        ]);
    }
}
