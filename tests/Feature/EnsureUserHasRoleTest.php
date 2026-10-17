<?php

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'rol:cajero,administrador'])
        ->get('/prueba-rol', fn () => 'ok');
});

it('redirects guests to login', function () {
    $this->get('/prueba-rol')->assertRedirect(route('login'));
});

it('forbids a user without the required role', function (UsuarioRol $rol) {
    $this->actingAs(User::factory()->create(['rol' => $rol]))
        ->get('/prueba-rol')
        ->assertForbidden();
})->with([
    'cliente' => UsuarioRol::Cliente,
    'coordinador' => UsuarioRol::Coordinador,
    'gerente' => UsuarioRol::Gerente,
]);

it('allows a user with one of the required roles', function (UsuarioRol $rol) {
    $this->actingAs(User::factory()->create(['rol' => $rol]))
        ->get('/prueba-rol')
        ->assertOk()
        ->assertSee('ok');
})->with([
    'cajero' => UsuarioRol::Cajero,
    'administrador' => UsuarioRol::Administrador,
]);
