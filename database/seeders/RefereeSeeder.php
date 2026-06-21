<?php

namespace Database\Seeders;

use App\Models\Referee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RefereeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Referee::create([
            'name' => 'John Smith',
            'email' => 'john.smith@rugbyref.com',
            'phone' => '+1234567890',
            'license_level' => 'Nazionale serie A Elite',
            'availability_status' => 'available',
        ]);

        Referee::create([
            'name' => 'Maria Garcia',
            'email' => 'maria.garcia@rugbyref.com',
            'phone' => '+0987654321',
            'license_level' => 'Nazionale serie A',
            'availability_status' => 'available',
        ]);

        Referee::create([
            'name' => 'David Wilson',
            'email' => 'david.wilson@rugbyref.com',
            'phone' => '+1122334455',
            'license_level' => 'Nazionale serie B',
            'availability_status' => 'limited',
        ]);

        Referee::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.johnson@rugbyref.com',
            'phone' => '+5566778899',
            'license_level' => 'Assistente serie A',
            'availability_status' => 'available',
        ]);
    }
}
