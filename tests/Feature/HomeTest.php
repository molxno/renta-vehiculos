<?php

use App\Enums\UsuarioRol;
use App\Enums\VehiculoEstado;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Vehiculo;

function inertiaHomeHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('landing pública', function () {
    it('renders the storefront home instead of the operational panel', function () {
        $this->withHeaders(inertiaHomeHeaders())
            ->get(route('home'))
            ->assertOk()
            ->assertJsonPath('component', 'Welcome')
            ->assertJsonPath('props.auth.user', null)
            ->assertJsonStructure([
                'props' => [
                    'featuredVehicles',
                    'search' => ['location', 'startDate', 'endDate'],
                ],
            ]);
    });

    it('exposes featured available vehicles with their daily rate', function () {
        $available = Vehiculo::factory()->create([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'tipo' => 'sedan',
            'estado' => VehiculoEstado::Disponible,
        ]);
        Tarifa::factory()->create([
            'vehiculo_id' => $available->id,
            'tipo_vehiculo' => 'sedan',
            'valor_diario_centavos' => 15000000,
        ]);
        $withoutRate = Vehiculo::factory()->create([
            'marca' => 'Kia',
            'modelo' => 'Picanto',
            'tipo' => 'compacto',
            'estado' => VehiculoEstado::Disponible,
        ]);
        Vehiculo::factory()->create([
            'estado' => VehiculoEstado::Mantenimiento,
        ]);

        $this->withHeaders(inertiaHomeHeaders())
            ->get(route('home'))
            ->assertOk()
            ->assertJsonPath('props.featuredVehicles.0.id', $available->id)
            ->assertJsonPath('props.featuredVehicles.0.label', 'Toyota Corolla')
            ->assertJsonPath('props.featuredVehicles.0.category', 'sedan')
            ->assertJsonPath('props.featuredVehicles.0.dailyRateCents', 15000000)
            ->assertJsonPath('props.featuredVehicles.1.id', $withoutRate->id)
            ->assertJsonPath('props.featuredVehicles.1.dailyRateCents', 0)
            ->assertJsonPath('props.featuredVehicles.1.label', 'Kia Picanto');
    });

    it('keeps the public landing for an authenticated coordinator', function () {
        $user = User::factory()->coordinador()->create();

        $this->actingAs($user)
            ->withHeaders(inertiaHomeHeaders())
            ->get(route('home'))
            ->assertOk()
            ->assertJsonPath('component', 'Welcome')
            ->assertJsonPath('props.auth.user.role', UsuarioRol::Coordinador->value)
            ->assertJsonMissingPath('props.reservations');
    });
});
