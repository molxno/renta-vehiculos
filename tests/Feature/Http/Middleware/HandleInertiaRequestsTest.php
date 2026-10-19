<?php

declare(strict_types=1);

use App\Enums\UsuarioRol;
use App\Models\User;

function cabecerasInertiaNavegacion(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

it('does not share navigation links for guests', function () {
    $this->withHeaders(cabecerasInertiaNavegacion())
        ->get(route('home'))
        ->assertOk()
        ->assertJsonPath('props.navigation', [])
        ->assertJsonPath('props.landingHref', route('home'));
});

it('shares only working navigation links for each role', function (UsuarioRol $rol) {
    $esperado = match ($rol) {
        UsuarioRol::Cliente => [
            ['label' => 'Mis reservas', 'href' => route('reservas.index')],
        ],
        UsuarioRol::Coordinador => [
            ['label' => 'Reservas', 'href' => route('reservas.index')],
            ['label' => 'Entregas', 'href' => route('entregas.index')],
            ['label' => 'Devoluciones', 'href' => route('devoluciones.index')],
        ],
        UsuarioRol::Cajero => [
            ['label' => 'Pagos', 'href' => route('pagos.index')],
        ],
        UsuarioRol::Administrador => [
            ['label' => 'Tasa de cambio', 'href' => route('admin.trm.edit')],
            ['label' => 'Entregas', 'href' => route('entregas.index')],
            ['label' => 'Devoluciones', 'href' => route('devoluciones.index')],
            ['label' => 'Pagos', 'href' => route('pagos.index')],
        ],
        UsuarioRol::Gerente => [],
    };
    $aterrizaje = match ($rol) {
        UsuarioRol::Cliente, UsuarioRol::Administrador => route('reservas.index'),
        UsuarioRol::Coordinador => route('entregas.index'),
        UsuarioRol::Cajero => route('pagos.index'),
        UsuarioRol::Gerente => route('home'),
    };

    $this->actingAs(User::factory()->create(['rol' => $rol]))
        ->withHeaders(cabecerasInertiaNavegacion())
        ->get(route('home'))
        ->assertOk()
        ->assertJsonPath('props.navigation', $esperado)
        ->assertJsonPath('props.landingHref', $aterrizaje);
})->with([
    'cliente' => UsuarioRol::Cliente,
    'coordinador' => UsuarioRol::Coordinador,
    'cajero' => UsuarioRol::Cajero,
    'administrador' => UsuarioRol::Administrador,
    'gerente' => UsuarioRol::Gerente,
]);
