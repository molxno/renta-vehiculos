<?php

use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Enums\VehiculoEstado;
use App\Models\Factura;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\MinimumCatalogSeeder;

function mvpInertiaHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file(
            'xxh128',
            public_path('build/manifest.json'),
        ),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

test('seeded MVP exposes the complete smoke flow', function () {
    $this->seed();

    $reservation = Reserva::where('estado', ReservaEstado::Confirmada)->firstOrFail();
    $invoice = Factura::query()->firstOrFail();
    $headers = mvpInertiaHeaders();

    $this->withHeaders($headers)
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Index');

    $this->withHeaders($headers)
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Show')
        ->assertJsonPath('props.reservation.id', $reservation->id);

    $this->withHeaders($headers)
        ->get(route('entregas.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Entregas/Create');

    $this->withHeaders($headers)
        ->get(route('devoluciones.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Devoluciones/Create');

    $this->withHeaders($headers)
        ->get(route('pagos.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Pagos/Create');

    $this->withHeaders($headers)
        ->get(route('facturas.show', $invoice))
        ->assertOk()
        ->assertJsonPath('component', 'Facturas/Show');
});

test('completes the MVP HTTP smoke flow from register to invoice', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $headers = mvpInertiaHeaders();

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Cliente Humo',
            'email' => 'cliente.humo@renta.test',
            'tipo_documento' => 'CC',
            'numero_documento' => '1099000111',
            'telefono' => '3001112233',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('home'));

    $cliente = User::query()->where('email', 'cliente.humo@renta.test')->firstOrFail();
    $this->assertAuthenticatedAs($cliente);
    expect($cliente->rol)->toBe(UsuarioRol::Cliente);

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'cliente.humo@renta.test',
            'password' => 'password',
        ])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($cliente);

    LicenciaConduccion::factory()->create([
        'user_id' => $cliente->id,
        'numero' => 'LIC-HUMO-001',
        'fecha_vencimiento' => '2028-12-31',
    ]);

    $catalogo = $this->withHeaders($headers)
        ->get(route('catalogo', [
            'startDate' => '2026-12-01',
            'endDate' => '2026-12-04',
            'location' => 'Medellín',
        ]))
        ->assertOk()
        ->assertJsonPath('component', 'Catalogo/Index')
        ->assertJsonPath('props.filters.startDate', '2026-12-01')
        ->assertJsonPath('props.filters.endDate', '2026-12-04');

    expect($catalogo->json('props.vehicles'))->not->toBeEmpty();

    $vehiculo = Vehiculo::query()->where('placa', 'ABC-123')->sole();

    $this->post(route('reservas.store'), [
        'user_id' => $cliente->id,
        'vehiculo_id' => $vehiculo->id,
        'inicio' => '2026-12-01 09:00:00',
        'fin' => '2026-12-04 09:00:00',
        'moneda' => 'COP',
    ])->assertRedirectToRoute(
        'reservas.show',
        $reserva = Reserva::query()->where('user_id', $cliente->id)->sole(),
    );

    expect($reserva->estado)->toBe(ReservaEstado::Confirmada)
        ->and($reserva->qr_codigo)->toBe('FAKE-QR-'.$reserva->id)
        ->and($reserva->total_centavos)->toBe(53550000)
        ->and($reserva->moneda)->toBe('COP');

    $this->withHeaders($headers)
        ->get(route('reservas.show', $reserva))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Show')
        ->assertJsonPath('props.reservation.qr_codigo', 'FAKE-QR-'.$reserva->id);

    $this->post(route('entregas.store', $reserva), [
        'entregado_at' => '2026-12-01 09:00:00',
        'checklist' => [
            'documentos' => true,
            'llantas' => true,
            'kilometraje' => '10000',
            'combustible' => 'lleno',
        ],
        'observaciones' => 'Entrega de humo',
        'aceptacion' => '1',
    ])->assertRedirectToRoute('reservas.show', $reserva);

    $reserva->refresh()->load(['vehiculo', 'actaEntrega', 'contrato']);
    expect($reserva->estado)->toBe(ReservaEstado::Entregada)
        ->and($reserva->vehiculo->estado)->toBe(VehiculoEstado::Alquilado)
        ->and($reserva->actaEntrega)->not->toBeNull()
        ->and($reserva->contrato)->not->toBeNull();

    $this->post(route('devoluciones.store', $reserva), [
        'devuelto_at' => '2026-12-04 09:00:00',
        'checklist' => [
            'kilometraje' => '10100',
            'combustible' => 'lleno',
        ],
        'observaciones' => 'Inspección de humo sin novedades',
    ])->assertRedirectToRoute('reservas.show', $reserva);

    $reserva->refresh();
    $factura = $reserva->factura()->sole();

    expect($reserva->estado)->toBe(ReservaEstado::Facturada)
        ->and($reserva->actaDevolucion)->not->toBeNull()
        ->and($factura->estado)->toBe(FacturaEstado::Pendiente)
        ->and($factura->numero)->toBe(sprintf('FAC-%06d', $reserva->id));

    $this->post(route('pagos.store', $reserva), [
        'metodo' => MetodoPago::Tarjeta->value,
        'monto_centavos' => $factura->total_centavos,
        'numero_tarjeta' => '4111111111111111',
    ])->assertRedirectToRoute('reservas.show', $reserva);

    $reserva->refresh();
    $factura->refresh();

    expect($reserva->estado)->toBe(ReservaEstado::Pagada)
        ->and($factura->estado)->toBe(FacturaEstado::Pagada)
        ->and($reserva->pagos()->where('estado', PagoEstado::Aprobado)->exists())->toBeTrue();

    $this->withHeaders($headers)
        ->get(route('facturas.show', $factura))
        ->assertOk()
        ->assertJsonPath('component', 'Facturas/Show')
        ->assertJsonPath('props.invoice.id', $factura->id)
        ->assertJsonPath('props.invoice.status', FacturaEstado::Pagada->value)
        ->assertJsonPath('props.invoice.numero', $factura->numero);
});

test('rejects creating a mock reservation when the vehicle is occupied', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $vehiculo = Vehiculo::query()->where('placa', 'ABC-123')->sole();
    $cliente = User::query()->where('email', 'cliente@renta.test')->sole();

    Reserva::factory()->create([
        'user_id' => $cliente->id,
        'vehiculo_id' => $vehiculo->id,
        'estado' => ReservaEstado::Confirmada,
        'inicio' => '2026-12-01 09:00:00',
        'fin' => '2026-12-04 09:00:00',
    ]);

    $this->from(route('reservas.create'))
        ->post(route('reservas.store'), [
            'user_id' => $cliente->id,
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-12-02 09:00:00',
            'fin' => '2026-12-05 09:00:00',
        ])
        ->assertRedirect(route('reservas.create'))
        ->assertSessionHasErrors('vehiculo_id');

    expect(Reserva::query()->where('vehiculo_id', $vehiculo->id)->count())->toBe(1);
});
