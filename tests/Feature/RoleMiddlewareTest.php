<?php

use App\Enums\UsuarioRol;
use App\Models\User;

test('administrator can access protected route', function () {
    $user = User::factory()->create([
        'rol' => UsuarioRol::Administrador,
    ]);

    $this->actingAs($user)
        ->get('/admin-test')
        ->assertOk();
});

test('client cannot access protected route', function () {
    $user = User::factory()->create([
        'rol' => UsuarioRol::Cliente,
    ]);

    $this->actingAs($user)
        ->get('/admin-test')
        ->assertForbidden();
});
