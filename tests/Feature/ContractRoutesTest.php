<?php

declare(strict_types=1);

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\PasarelaPago;
use App\Contracts\TarifaService;
use App\Fakes\FakeDisponibilidadService;
use App\Fakes\FakeGeneradorQr;
use App\Models\Reserva;
use App\Models\User;
use App\Services\Adapters\PasarelaPagoAdapter;
use App\Services\CalculadoraDevolucionEstandar;
use App\Services\TarifaServiceEstandar;
use Illuminate\Support\Facades\Route;

test('contract routes expose the documented pages and redirects', function () {
    $routes = Route::getRoutes();

    expect($routes->getByName('catalogo')->methods())->toContain('GET')
        ->and($routes->getByName('reservas.index')->methods())->toContain('GET')
        ->and($routes->getByName('reservas.create')->methods())->toContain('GET')
        ->and($routes->getByName('reservas.store')->methods())->toContain('POST')
        ->and($routes->getByName('reservas.qr')->methods())->toContain('GET')
        ->and($routes->getByName('reservas.confirmar')->methods())->toContain('POST')
        ->and($routes->getByName('reservas.cancelar')->methods())->toContain('POST')
        ->and($routes->getByName('entregas.index')->methods())->toContain('GET')
        ->and($routes->getByName('entregas.create')->methods())->toContain('GET')
        ->and($routes->getByName('entregas.store')->methods())->toContain('POST')
        ->and($routes->getByName('entregas.show')->methods())->toContain('GET')
        ->and($routes->getByName('entregas.acta')->methods())->toContain('GET')
        ->and($routes->getByName('devoluciones.index')->methods())->toContain('GET')
        ->and($routes->getByName('devoluciones.create')->methods())->toContain('GET')
        ->and($routes->getByName('devoluciones.store')->methods())->toContain('POST')
        ->and($routes->getByName('devoluciones.show')->methods())->toContain('GET')
        ->and($routes->getByName('facturas.show')->methods())->toContain('GET')
        ->and($routes->getByName('pagos.index')->methods())->toContain('GET')
        ->and($routes->getByName('pagos.create')->methods())->toContain('GET')
        ->and($routes->getByName('pagos.store')->methods())->toContain('POST')
        ->and($routes->getByName('pagos.show')->methods())->toContain('GET')
        ->and($routes->getByName('admin.trm.edit')->methods())->toContain('GET')
        ->and($routes->getByName('admin.trm.update')->methods())->toContain('PUT')
        ->and($routes->getByName('cuenta.show')->methods())->toContain('GET')
        ->and($routes->getByName('cuenta.update')->methods())->toContain('PUT');

    $this->actingAs(User::factory()->cliente()->create())
        ->post(route('reservas.store'), [])
        ->assertSessionHasErrors([
            'vehiculo_id',
            'inicio',
            'fin',
        ]);
});

test('application contracts resolve to the phase fakes', function () {
    expect(app(DisponibilidadService::class))->toBeInstanceOf(FakeDisponibilidadService::class)
        ->and(app(TarifaService::class))->toBeInstanceOf(TarifaServiceEstandar::class)
        ->and(app(PasarelaPago::class))->toBeInstanceOf(PasarelaPagoAdapter::class)
        ->and(app(GeneradorQr::class))->toBeInstanceOf(FakeGeneradorQr::class)
        ->and(app(CalculadoraDevolucion::class))->toBeInstanceOf(CalculadoraDevolucionEstandar::class);
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

    $this->actingAs(User::factory()->cliente()->create())
        ->withHeaders($headers)
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
