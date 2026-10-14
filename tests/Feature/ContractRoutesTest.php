<?php

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\PasarelaPago;
use App\Contracts\TarifaService;
use App\Fakes\FakeCalculadoraDevolucion;
use App\Fakes\FakeDisponibilidadService;
use App\Fakes\FakeGeneradorQr;
use App\Fakes\FakePasarelaPago;
use App\Fakes\FakeTarifaService;
use App\Models\Reserva;
use Illuminate\Support\Facades\Route;

test('contract routes expose the documented pages and redirects', function () {
    $routes = Route::getRoutes();

    expect($routes->getByName('reservas.index')->methods())->toContain('GET')
        ->and($routes->getByName('reservas.create')->methods())->toContain('GET')
        ->and($routes->getByName('reservas.store')->methods())->toContain('POST')
        ->and($routes->getByName('reservas.confirmar')->methods())->toContain('POST')
        ->and($routes->getByName('reservas.cancelar')->methods())->toContain('POST')
        ->and($routes->getByName('entregas.create')->methods())->toContain('GET')
        ->and($routes->getByName('entregas.store')->methods())->toContain('POST')
        ->and($routes->getByName('devoluciones.create')->methods())->toContain('GET')
        ->and($routes->getByName('devoluciones.store')->methods())->toContain('POST')
        ->and($routes->getByName('facturas.show')->methods())->toContain('GET')
        ->and($routes->getByName('pagos.create')->methods())->toContain('GET')
        ->and($routes->getByName('pagos.store')->methods())->toContain('POST');

    $this->post(route('reservas.store'), [])->assertSessionHasErrors([
        'user_id',
        'vehiculo_id',
        'inicio',
        'fin',
    ]);
});

test('application contracts resolve to the phase fakes', function () {
    expect(app(DisponibilidadService::class))->toBeInstanceOf(FakeDisponibilidadService::class)
        ->and(app(TarifaService::class))->toBeInstanceOf(FakeTarifaService::class)
        ->and(app(PasarelaPago::class))->toBeInstanceOf(FakePasarelaPago::class)
        ->and(app(GeneradorQr::class))->toBeInstanceOf(FakeGeneradorQr::class)
        ->and(app(CalculadoraDevolucion::class))->toBeInstanceOf(FakeCalculadoraDevolucion::class);
});

test('reservation pages expose their documented Inertia props', function () {
    $reservation = Reserva::factory()->create();

    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file(
            'xxh128',
            public_path('build/manifest.json'),
        ),
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    $this->withHeaders($headers)
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Index')
        ->assertJsonStructure(['props' => ['reservations', 'filters']]);

    $this->withHeaders($headers)
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Show')
        ->assertJsonPath('props.reservation.id', $reservation->id);
});
