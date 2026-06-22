<?php

use Database\Seeders\DatabaseSeeder;

test('the seeded admin can authenticate with the default password', function () {
    $this->seed(DatabaseSeeder::class);

    $response = $this->post('/login', [
        'email' => 'admin@designatore.it',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
