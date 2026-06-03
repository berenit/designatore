<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name'  => 'Designatore Admin',
            'email' => 'admin@designatore.it',
            'role'  => 'designatore',
        ]);

        $this->call([
            RefereeSeeder::class,
            TeamSeeder::class,
        ]);
    }
}
