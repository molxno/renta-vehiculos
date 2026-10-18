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

    $this->actingAs(User::query()->where('email', 'cliente@renta.test')->sole())
        ->withHeaders($headers)
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Index');

    $this->actingAs(User::query()->where('email', 'cliente@renta.test')->sole())
        ->withHeaders($headers)
        ->get(route('reservas.create'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Create')
        ->assertJsonStructure([
            'props' => [
                'vehicles' => ['*' => ['id', 'label', 'dailyRateCents']],
                'filters' => ['vehiculoId', 'inicio', 'fin'],
                'trm',
                'quote',
            ],
        ]);

    $this->withHeaders($headers)
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Show')
        ->assertJsonPath('props.reservation.id', $reservation->id)
        ->assertJsonPath('props.reservation.qrCode', $reservation->qr_codigo)
        ->assertJsonPath('props.reservation.vehiclePlate', $reservation->vehiculo->placa);

    $this->actingAs(User::query()->where('email', 'coordinador@renta.test')->sole())
        ->withHeaders($headers)
        ->get(route('entregas.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Entregas/Create');

    $this->actingAs(User::query()->where('email', 'coordinador@renta.test')->sole())
        ->withHeaders($headers)
        ->get(route('devoluciones.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Devoluciones/Create');

    $this->actingAs(User::query()->where('email', 'cajero@renta.test')->sole())
        ->withHeaders($headers)
        ->get(route('pagos.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Pagos/Create');

    $this->actingAs(User::query()->where('email', 'cajero@renta.test')->sole())
        ->withHeaders($headers)
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
        'vehiculo_id' => $vehiculo->id,
        'inicio' => '2026-12-01 09:00:00',
        'fin' => '2026-12-04 09:00:00',
    ])->assertRedirectToRoute(
        'reservas.show',
        $reserva = Reserva::query()->where('user_id', $cliente->id)->sole(),
    );

    expect($reserva->estado)->toBe(ReservaEstado::Pendiente)
        ->and($reserva->qr_codigo)->toBeNull()
        ->and($reserva->total_centavos)->toBe(53550000)
        ->and($reserva->moneda)->toBe('COP');

    $this->post(route('reservas.confirmar', $reserva))
        ->assertRedirectToRoute('reservas.show', $reserva);

    $reserva->refresh();

    expect($reserva->estado)->toBe(ReservaEstado::Confirmada)
        ->and($reserva->qr_codigo)->toBe('FAKE-QR-'.$reserva->id);

    $this->withHeaders($headers)
        ->get(route('reservas.show', $reserva))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Show')
        ->assertJsonPath('props.reservation.qrCode', 'FAKE-QR-'.$reserva->id)
        ->assertJsonPath('props.reservation.totalCents', 53550000);

    $this->actingAs(User::query()->where('email', 'coordinador@renta.test')->sole());

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
        ->and($reserva->vehiculo->estado)->toBe(VehiculoEstado::Disponible)
        ->and($reserva->actaDevolucion)->not->toBeNull()
        ->and($factura->estado)->toBe(FacturaEstado::Pendiente)
        ->and($factura->numero)->toBe(sprintf('FAC-%06d', $reserva->id));

    $this->actingAs(User::query()->where('email', 'cajero@renta.test')->sole());

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

    $this->actingAs($cliente)
        ->from(route('reservas.create'))
        ->post(route('reservas.store'), [
            'vehiculo_id' => $vehiculo->id,
            'inicio' => '2026-12-02 09:00:00',
            'fin' => '2026-12-05 09:00:00',
        ])
        ->assertRedirect(route('reservas.create'))
        ->assertSessionHasErrors('vehiculo_id');

    expect(Reserva::query()->count())->toBe(1)
        ->and(Reserva::query()->where('vehiculo_id', $vehiculo->id)->count())->toBe(1);
});

test('shows a server quote and the persisted TRM for selected available dates', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $client = User::query()->where('email', 'cliente@renta.test')->sole();
    $vehicle = Vehiculo::query()->where('placa', 'ABC-123')->sole();
    $headers = mvpInertiaHeaders();

    $this->actingAs($client)
        ->withHeaders($headers)
        ->get(route('reservas.create', [
            'vehiculo_id' => $vehicle->id,
            'inicio' => '2026-12-01',
            'fin' => '2026-12-04',
        ]))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Create')
        ->assertJsonPath('props.filters.vehiculoId', $vehicle->id)
        ->assertJsonPath('props.vehicles.0.dailyRateCents', 15000000)
        ->assertJsonPath('props.quote.subtotalCents', 45000000)
        ->assertJsonPath('props.quote.totalCents', 53550000)
        ->assertJsonPath('props.quote.currency', 'COP')
        ->assertJsonPath('props.trm', '4200.0000');
});

test('requires a client session to open the reservation form', function () {
    $this->get(route('reservas.create'))
        ->assertRedirectToRoute('login');
});

test('forbids staff from opening the reservation form', function () {
    $this->actingAs(User::factory()->coordinador()->create())
        ->get(route('reservas.create'))
        ->assertForbidden();
});

test('suggests other criteria when no vehicles are available to reserve', function () {
    $this->actingAs(User::factory()->cliente()->create());
    Vehiculo::factory()->create(['estado' => VehiculoEstado::Mantenimiento]);

    $this->withHeaders(mvpInertiaHeaders())
        ->get(route('reservas.create'))
        ->assertOk()
        ->assertJsonPath('props.vehicles', [])
        ->assertJsonPath('props.trm', null)
        ->assertJsonPath('props.quote', null);
});

test('rejects invalid dates when requesting a reservation quote', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $client = User::query()->where('email', 'cliente@renta.test')->sole();
    $vehicle = Vehiculo::query()->where('placa', 'ABC-123')->sole();

    $this->actingAs($client)
        ->from(route('reservas.create'))
        ->get(route('reservas.create', [
            'vehiculo_id' => $vehicle->id,
            'inicio' => '2026-12-04',
            'fin' => '2026-12-01',
        ]))
        ->assertRedirect(route('reservas.create'))
        ->assertSessionHasErrors([
            'fin' => 'La fecha de devolución debe ser posterior a la fecha de recogida.',
        ]);
});

test('creates a reservation for the authenticated client instead of a submitted owner', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $client = User::factory()->cliente()->create();
    $otherClient = User::factory()->cliente()->create();
    $vehicle = Vehiculo::query()->where('placa', 'ABC-123')->sole();

    $this->actingAs($client)
        ->post(route('reservas.store'), [
            'user_id' => $otherClient->id,
            'vehiculo_id' => $vehicle->id,
            'inicio' => '2026-12-01 09:00:00',
            'fin' => '2026-12-04 09:00:00',
        ])
        ->assertRedirectToRoute('reservas.show', $reservation = Reserva::query()->sole());

    expect($reservation->user_id)->toBe($client->id);
});

test('requires a client session to create a reservation', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $vehicle = Vehiculo::query()->where('placa', 'ABC-123')->sole();

    $this->post(route('reservas.store'), [
        'vehiculo_id' => $vehicle->id,
        'inicio' => '2026-12-01 09:00:00',
        'fin' => '2026-12-04 09:00:00',
    ])->assertRedirectToRoute('login');

    expect(Reserva::query()->count())->toBe(0);
});

test('forbids staff from creating a client reservation', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $staff = User::factory()->coordinador()->create();
    $vehicle = Vehiculo::query()->where('placa', 'ABC-123')->sole();

    $this->actingAs($staff)
        ->post(route('reservas.store'), [
            'vehiculo_id' => $vehicle->id,
            'inicio' => '2026-12-01 09:00:00',
            'fin' => '2026-12-04 09:00:00',
        ])
        ->assertForbidden();

    expect(Reserva::query()->count())->toBe(0);
});
