<?php

use App\Models\User;

test('login page can be rendered', function () {
    $this->get('/login')
        ->assertOk();
});

test('register page can be rendered', function () {
    $this->get('/registro')
        ->assertOk();
});

test('user can register', function () {
    $response = $this->post('/registro', [
        'name' => 'Andres',
        'email' => 'andres@test.com',
        'tipo_documento' => 'CC',
        'numero_documento' => '123456789',
        'telefono' => '3001234567',
        'password' => 'password123',
    ]);

    $response->assertRedirect();

    expect(
        User::where('email', 'andres@test.com')->exists()
    )->toBeTrue();
});

test('user can login', function () {
    $user = User::factory()->create([
        'email' => 'login@test.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'login@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect();

    $this->assertAuthenticatedAs($user);
});
