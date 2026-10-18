<?php

use App\Contracts\DisponibilidadService;
use App\Enums\VehiculoEstado;
use App\Models\Tarifa;
use App\Models\Vehiculo;
use Database\Seeders\MinimumCatalogSeeder;
use DateTimeInterface;

function inertiaCatalogoHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('catálogo de vehículos', function () {
    it('renders the public catalog from seeded vehicles, categories and rates', function () {
        $this->seed(MinimumCatalogSeeder::class);

        $response = $this->withHeaders(inertiaCatalogoHeaders())
            ->get(route('catalogo'))
            ->assertOk()
            ->assertJsonPath('component', 'Catalogo/Index')
            ->assertJsonPath('props.auth.user', null)
            ->assertJsonPath('props.vehicles.0.label', 'Toyota Corolla')
            ->assertJsonPath('props.vehicles.0.brand', 'Toyota')
            ->assertJsonPath('props.vehicles.0.model', 'Corolla')
            ->assertJsonPath('props.vehicles.0.category', 'sedan')
            ->assertJsonPath('props.vehicles.0.status', VehiculoEstado::Disponible->value)
            ->assertJsonPath('props.vehicles.0.dailyRateCents', 15000000)
            ->assertJsonPath('props.vehicles.1.label', 'Renault Duster')
            ->assertJsonPath('props.vehicles.1.category', 'camioneta')
            ->assertJsonPath('props.vehicles.1.dailyRateCents', 22000000)
            ->assertJsonPath('props.vehicles.2.label', 'Kia Picanto')
            ->assertJsonPath('props.vehicles.2.category', 'compacto')
            ->assertJsonPath('props.filters.category', '')
            ->assertJsonPath('props.filters.startDate', '')
            ->assertJsonPath('props.filters.endDate', '')
            ->assertJsonPath('props.filters.location', '');

        expect($response->json('props.categories'))->toEqual(['camioneta', 'compacto', 'sedan']);
    });

    it('filters vehicles by category and keeps every category in the sidebar', function () {
        $sedan = Vehiculo::factory()->create([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'tipo' => 'sedan',
        ]);
        Tarifa::factory()->create([
            'vehiculo_id' => $sedan->id,
            'tipo_vehiculo' => 'sedan',
            'valor_diario_centavos' => 15000000,
        ]);
        Vehiculo::factory()->create([
            'marca' => 'Renault',
            'modelo' => 'Duster',
            'tipo' => 'camioneta',
        ]);

        $response = $this->withHeaders(inertiaCatalogoHeaders())
            ->get(route('catalogo', ['category' => 'sedan']))
            ->assertOk()
            ->assertJsonPath('props.vehicles.0.id', $sedan->id)
            ->assertJsonPath('props.filters.category', 'sedan');

        expect($response->json('props.vehicles'))->toHaveCount(1)
            ->and($response->json('props.categories'))->toEqual(['camioneta', 'sedan']);
    });

    it('excludes vehicles that the availability service marks as unavailable', function () {
        $available = Vehiculo::factory()->create([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);
        Tarifa::factory()->create(['vehiculo_id' => $available->id]);
        $unavailable = Vehiculo::factory()->create([
            'marca' => 'Kia',
            'modelo' => 'Picanto',
        ]);
        Tarifa::factory()->create(['vehiculo_id' => $unavailable->id]);

        $this->app->instance(DisponibilidadService::class, new class($available->id) implements DisponibilidadService
        {
            public function __construct(private int $availableId) {}

            public function consultar(
                DateTimeInterface $inicio,
                DateTimeInterface $fin,
                ?int $vehiculoId = null,
            ): array {
                return [
                    [
                        'vehiculo_id' => $this->availableId,
                        'disponible' => true,
                        'motivo' => null,
                    ],
                ];
            }
        });

        $response = $this->withHeaders(inertiaCatalogoHeaders())
            ->get(route('catalogo', [
                'startDate' => '2026-11-01',
                'endDate' => '2026-11-05',
                'location' => 'Medellín',
            ]))
            ->assertOk()
            ->assertJsonPath('props.vehicles.0.id', $available->id)
            ->assertJsonPath('props.filters.startDate', '2026-11-01')
            ->assertJsonPath('props.filters.endDate', '2026-11-05')
            ->assertJsonPath('props.filters.location', 'Medellín');

        expect($response->json('props.vehicles'))->toHaveCount(1);
    });

    it('keeps vehicles without a current rate in the listing', function () {
        $withoutRate = Vehiculo::factory()->create([
            'marca' => 'Kia',
            'modelo' => 'Picanto',
            'tipo' => 'compacto',
            'estado' => VehiculoEstado::Disponible,
        ]);

        $this->withHeaders(inertiaCatalogoHeaders())
            ->get(route('catalogo'))
            ->assertOk()
            ->assertJsonPath('props.vehicles.0.id', $withoutRate->id)
            ->assertJsonPath('props.vehicles.0.dailyRateCents', 0);
    });

    it('rejects a return date that is not after the pickup date', function () {
        $this->from(route('catalogo'))
            ->get(route('catalogo', [
                'startDate' => '2026-11-05',
                'endDate' => '2026-11-01',
            ]))
            ->assertRedirect(route('catalogo'))
            ->assertSessionHasErrors([
                'endDate' => 'La fecha de devolución debe ser posterior a la de recogida.',
            ]);
    });

    it('rejects a pickup date without a return date', function () {
        $this->from(route('catalogo'))
            ->get(route('catalogo', ['startDate' => '2026-11-01']))
            ->assertRedirect(route('catalogo'))
            ->assertSessionHasErrors([
                'endDate' => 'Indica la fecha de recogida y la de devolución.',
            ]);
    });
});
